<?php

namespace App\Services;

/**
 * HTTP header management service.
 *
 * Handles response headers, CORS configuration, caching policies,
 * and basic security headers.
 *
 * CORS is restricted to explicitly allowed origins.
 * No origin is allowed unless it is present in the allowed_host list.
 */
class HeaderService
{
    /** List of origins permitted to access this API. */
    public array $allowed_host = ["http://localhost:5173"];

    /** Response Content-Type header value. */
    public string $content_type = "";

    /** HTTP methods permitted by the CORS policy. */
    public array $allowed_methods = [];

    /** HTTP headers permitted by the CORS policy. */
    public array $allowed_headers = [];

    /** Maximum time, in seconds, that preflight results can be cached. */
    public int $max_age = 0;

    /** Determines whether HTTP response caching is enabled. */
    public bool $cache = false;

    /**
     * Initializes the default header configuration.
     *
     * Sets JSON as the default response content type,
     * allows Content-Type headers, enables OPTIONS requests,
     * and disables response caching.
     */
    function __construct()
    {
        $this->setContentType("application/json");
        $this->addAllowedHeaders("Content-Type");
        $this->addAllowedMethods("OPTIONS");
        $this->setMaxAge(86400);
        $this->setCache(false);
    }

    // ------------------------------------------------------------------
    // Configuration setters
    // ------------------------------------------------------------------

    /** Sets the response Content-Type header. */
    public function setContentType(string $type_): void
    {
        $this->content_type = $type_;
    }

    /** Replaces the list of allowed HTTP methods. */
    public function setAllowedMethods(array $methods_): void
    {
        $this->allowed_methods = $methods_;
    }

    /** Replaces the list of allowed request headers. */
    public function setAllowedHeaders(array $headers_): void
    {
        $this->allowed_headers = $headers_;
    }

    /** Sets the maximum age of CORS preflight cache entries. */
    public function setMaxAge(int $age_): void
    {
        $this->max_age = $age_;
    }

    /** Enables or disables HTTP response caching. */
    public function setCache(bool $cache_): void
    {
        $this->cache = $cache_;
    }

    // ------------------------------------------------------------------
    // Configuration appenders
    // ------------------------------------------------------------------

    /**
     * Adds one or more HTTP methods to the allowed methods list.
     *
     * Accepts either a single method string or an array of methods.
     */
    public function addAllowedMethods(array|string $methods_): void
    {
        if (is_string($methods_)) {
            array_push($this->allowed_methods, $methods_);
        } else {
            foreach ($methods_ as $m) {
                array_push($this->allowed_methods, $m);
            }
        }
    }

    /**
     * Adds one or more headers to the allowed request headers list.
     *
     * Accepts either a single header string or an array of headers.
     */
    public function addAllowedHeaders(array|string $headers_): void
    {
        if (is_string($headers_)) {
            array_push($this->allowed_headers, $headers_);
        } else {
            foreach ($headers_ as $h) {
                array_push($this->allowed_headers, $h);
            }
        }
    }

    // ------------------------------------------------------------------
    // Header application
    // ------------------------------------------------------------------

    /**
     * Applies all configured HTTP response headers.
     *
     * Includes:
     * - Basic security headers.
     * - Content-Type and charset configuration.
     * - Cache-Control and Pragma policies.
     * - CORS origin, methods, and headers configuration.
     *
     * Automatically returns HTTP 204 for OPTIONS requests.
     */
    public function apply()
    {
        // Basic security headers.
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");

        // Response content type and character encoding.
        header("Content-Type: " . $this->content_type . "; charset=utf-8");

        // Configure HTTP response caching.
        if ($this->cache == false) {
            header(
                "Cache-Control: no-store, no-cache, must-revalidate, max-age=0",
            );
            header("Pragma: no-cache");
        } else {
            header("Cache-Control: public, max-age=3600");
        }

        // Check whether the request origin is explicitly allowed.
        $origin = $_SERVER["HTTP_ORIGIN"] ?? "";
        if (in_array($origin, $this->allowed_host, true)) {
            header("Access-Control-Allow-Origin: $origin");
        }

        // CORS configuration headers.
        header("Vary: Origin");
        header(
            "Access-Control-Allow-Methods: " .
                implode(", ", $this->allowed_methods),
        );
        header(
            "Access-Control-Allow-Headers: " .
                implode(", ", $this->allowed_headers),
        );
        header("Access-Control-Max-Age: " . $this->max_age);

        // Terminate CORS preflight requests with an empty 204 response.
        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            http_response_code(204);
        }
    }
}
