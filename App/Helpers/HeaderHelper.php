<?php

namespace App\Helpers;

/**
 * HTTP request header utility.
 *
 * Provides a consistent way to retrieve HTTP request headers
 * across different PHP server environments.
 *
 * Supports getallheaders() and falls back to $_SERVER
 * when the function is unavailable or returns no headers.
 */
class HeaderHelper
{
    /**
     * Retrieves all HTTP request headers as an associative array.
     *
     * Uses getallheaders() when available. Otherwise, manually
     * extracts headers from $_SERVER and handles special cases
     * such as Authorization, Content-Type, and Content-Length.
     *
     * Also supports Apache authorization redirects and Basic Auth.
     *
     * @return array<string, string> All available request headers.
     */
    public static function all(): array
    {
        $headers = [];

        // 1. Use getallheaders() directly when available (Apache or modern PHP-FPM).
        if (function_exists("getallheaders")) {
            $fetchedHeaders = getallheaders();
            if ($fetchedHeaders !== false) {
                $headers = $fetchedHeaders;
            }
        }

        // 2. If getallheaders() is unavailable or returns no headers, collect them manually from $_SERVER.
        if (empty($headers)) {
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, "HTTP_")) {
                    // Converts HTTP_AUTHORIZATION into the Authorization header format.
                    $headerName = str_replace(
                        " ",
                        "-",
                        ucwords(
                            strtolower(str_replace("_", " ", substr($name, 5))),
                        ),
                    );
                    $headers[$headerName] = $value;
                }
            }

            // Add special headers that do not use the HTTP_ prefix.
            if (isset($_SERVER["CONTENT_TYPE"])) {
                $headers["Content-Type"] = $_SERVER["CONTENT_TYPE"];
            }
            if (isset($_SERVER["CONTENT_LENGTH"])) {
                $headers["Content-Length"] = $_SERVER["CONTENT_LENGTH"];
            }
        }

        // 3. Handle Apache authorization redirects and Basic Auth fallbacks.
        if (
            !isset($headers["Authorization"]) &&
            !isset($headers["authorization"])
        ) {
            if (isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
                $headers["Authorization"] =
                    $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
            } elseif (isset($_SERVER["PHP_AUTH_USER"])) {
                $pass = $_SERVER["PHP_AUTH_PW"] ?? "";
                $headers["Authorization"] =
                    "Basic " .
                    base64_encode($_SERVER["PHP_AUTH_USER"] . ":" . $pass);
            }
        }

        return $headers;
    }

    /**
     * Retrieves a specific request header (case-insensitive).
     *
     * Header names are normalized to lowercase before lookup,
     * ensuring consistent retrieval regardless of capitalization.
     *
     * @param string $key Header name to retrieve.
     * @param string|null $default Value returned if the header does not exist.
     *
     * @return string|null Header value or the default value.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $headers = self::all();

        // Convert all header keys to lowercase for case-insensitive lookup.
        $headersLower = array_change_key_case($headers, CASE_LOWER);

        return $headersLower[strtolower($key)] ?? $default;
    }
}
