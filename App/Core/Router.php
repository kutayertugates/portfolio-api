<?php

namespace App\Core;

use App\Services\HeaderService;

class Router
{
    /** @var array<int, array> */
    private array $routes = [];

    /** @var array<int, array> */
    private array $groupStack = [];

    /** @var callable[] */
    private array $globalMiddleware = [];

    /** @var array<string, int> */
    private array $namedRoutes = [];

    /** CORS ayarlı değilse router hiçbir CORS davranışı göstermez */
    private ?HeaderService $cors = null;

    // ------------------------------------------------------------------
    // CORS (opsiyonel)
    // ------------------------------------------------------------------

    public function setCors(HeaderService $headerService): self
    {
        $this->cors = $headerService;
        return $this;
    }

    public function hasCors(): bool
    {
        return $this->cors !== null;
    }

    // ------------------------------------------------------------------
    // Route tanımlama
    // ------------------------------------------------------------------

    public function get(string $path, $handler): self
    {
        return $this->add("GET", $path, $handler);
    }
    public function post(string $path, $handler): self
    {
        return $this->add("POST", $path, $handler);
    }
    public function put(string $path, $handler): self
    {
        return $this->add("PUT", $path, $handler);
    }
    public function patch(string $path, $handler): self
    {
        return $this->add("PATCH", $path, $handler);
    }
    public function delete(string $path, $handler): self
    {
        return $this->add("DELETE", $path, $handler);
    }
    public function options(string $path, $handler): self
    {
        return $this->add("OPTIONS", $path, $handler);
    }

    /** Tüm HTTP metotları için eşleşir */
    public function any(string $path, $handler): self
    {
        return $this->add("ANY", $path, $handler);
    }

    public function add(string $method, string $path, $handler): self
    {
        $prefix = "";
        $middleware = [];

        foreach ($this->groupStack as $group) {
            $p = trim($group["prefix"] ?? "", "/");
            if ($p !== "") {
                $prefix .= "/" . $p;
            }
            $middleware = array_merge($middleware, $group["middleware"] ?? []);
        }

        $segments = array_filter(
            [trim($prefix, "/"), trim($path, "/")],
            static fn($s) => $s !== "",
        );

        $fullPath = "/" . implode("/", $segments);

        $this->routes[] = [
            "method" => strtoupper($method),
            "path" => $fullPath,
            "pattern" => $this->compile($fullPath),
            "handler" => $handler,
            "middleware" => $middleware,
            "name" => null,
        ];

        return $this;
    }

    public function name(string $name): self
    {
        $index = count($this->routes) - 1;
        $this->routes[$index]["name"] = $name;
        $this->namedRoutes[$name] = $index;
        return $this;
    }

    // ------------------------------------------------------------------
    // Grup & middleware
    // ------------------------------------------------------------------

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function addMiddleware(callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    // ------------------------------------------------------------------
    // Dispatch
    // ------------------------------------------------------------------

    public function dispatch(string $method, string $uri): void
    {
        $requestMethod = strtoupper($method);
        $lookupMethod = $requestMethod === "HEAD" ? "GET" : $requestMethod;

        $path = parse_url($uri, PHP_URL_PATH) ?? "/";
        $path = rawurldecode($path);
        if ($path === "") {
            $path = "/";
        }

        // CORS ayarlıysa tüm yanıtlara header'ları uygula.
        // NOT: HeaderService::apply() OPTIONS isteğinde 204 set eder;
        // explicit ->options() route'un varsa handler'da
        // http_response_code(...) ile override edebilirsin.
        if ($this->cors !== null) {
            $this->cors->apply();
        }

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (
                !preg_match(
                    $route["pattern"],
                    $path,
                    $matches,
                    PREG_UNMATCHED_AS_NULL,
                )
            ) {
                continue;
            }

            if (
                $route["method"] !== $lookupMethod &&
                $route["method"] !== "ANY"
            ) {
                $allowedMethods[] = $route["method"];
                continue;
            }

            $params = array_filter(
                $matches,
                static fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY,
            );

            // Eşleşmeyen opsiyonel parametreler (null) handler'a geçmesin
            // → handler imzasındaki default devreye girsin.
            $params = array_filter($params, static fn($v) => $v !== null);

            $middleware = array_merge(
                $this->globalMiddleware,
                $route["middleware"],
            );
            $core = fn() => $this->invoke($route["handler"], $params);
            $pipeline = $this->buildPipeline($middleware, $core);

            $result = $pipeline();

            if (
                $requestMethod !== "HEAD" &&
                (is_string($result) || is_int($result) || is_float($result))
            ) {
                echo $result;
            }

            return;
        }

        // Fallback: hiçbir route path'e uymadı
        if ($allowedMethods !== []) {
            $allowedMethods = array_values(array_unique($allowedMethods));

            // CORS ayarlıysa OPTIONS fallback'i (preflight)
            if ($requestMethod === "OPTIONS" && $this->cors !== null) {
                header(
                    "Allow: " .
                        implode(
                            ", ",
                            array_merge($allowedMethods, ["OPTIONS"]),
                        ),
                );
                http_response_code(204);
                return;
            }

            header("Allow: " . implode(", ", $allowedMethods));
            http_response_code(405);
            echo "405 Method Not Allowed";
            return;
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    // ------------------------------------------------------------------
    // URL üretimi
    // ------------------------------------------------------------------

    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route bulunamadı: {$name}");
        }

        $path = $this->routes[$this->namedRoutes[$name]]["path"];

        foreach ($params as $key => $value) {
            $path = preg_replace(
                "#\{" . preg_quote((string) $key, "#") . "\??\}#",
                rawurlencode((string) $value),
                $path,
            );
        }

        return $path;
    }

    // ------------------------------------------------------------------
    // Yardımcılar
    // ------------------------------------------------------------------

    private function compile(string $path): string
    {
        $pattern = preg_replace_callback(
            "#\{([a-zA-Z_][a-zA-Z0-9_]*)(\?)?\}#",
            static fn(array $m): string => ($m[2] ?? null) === "?"
                ? "(?P<" . $m[1] . ">[^/]+)?"
                : "(?P<" . $m[1] . ">[^/]+)",
            $path,
        );

        $pattern ??= $path;

        return "#^" . rtrim($pattern, "/") . '/?$#';
    }

    private function buildPipeline(array $middleware, callable $core): callable
    {
        $next = $core;

        foreach (array_reverse($middleware) as $mw) {
            $next = static fn() => $mw($next);
        }

        return $next;
    }

    private function invoke($handler, array $params)
    {
        $args = array_values($params);

        if (
            $handler instanceof \Closure ||
            (is_callable($handler) &&
                !is_string($handler) &&
                !is_array($handler))
        ) {
            return $handler(...$args);
        }

        if (is_string($handler) && str_contains($handler, "@")) {
            [$class, $method] = explode("@", $handler, 2);
            if (!class_exists($class)) {
                throw new \RuntimeException("Controller bulunamadı: {$class}");
            }
            return (new $class())->{$method}(...$args);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class)) {
                $class = new $class();
            }
            return $class->{$method}(...$args);
        }

        throw new \RuntimeException("Geçersiz route handler.");
    }
}
