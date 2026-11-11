<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JSON Web Token (JWT) management service.
 *
 * Handles JWT generation and verification using the HS256 signing algorithm.
 * The signing secret is retrieved from the JWT_SECRET environment variable.
 */
class JWTService
{
    /**
     * Generates a signed JWT.
     *
     * Creates a payload containing:
     * - iat: Token issuance timestamp.
     * - exp: Token expiration timestamp (1 hour after issuance).
     * - data: Custom application data.
     *
     * @param mixed $_data Data to be embedded in the token payload.
     * @return string Encoded JWT.
     */

    public static int $expiration = 3600; // 1 hour

    public static function generate($_data)
    {
        $iat = time();
        $exp = time() + self::$expiration;
        $payload = [
            "iat" => $iat,
            "exp" => $exp,
            "data" => $_data,
        ];

        return [
            JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256"),
            $exp,
            $iat,
        ];
    }

    /**
     * Decodes and verifies a JWT.
     *
     * Validates the token signature and expiration time.
     *
     * @param string $jwt Encoded JWT to be verified.
     *
     * @return array|int
     * Returns an array containing:
     * - [true, 200, $decoded] on successful verification.
     * - [false, 401, "Token has expired"] if the token has expired.
     * - [false, 400, "Invalid token"] if the token is malformed or invalid.
     */
    public static function decode($jwt)
    {
        try {
            $decoded = JWT::decode($jwt, new Key($_ENV["JWT_SECRET"], "HS256"));
            return [true, 200, $decoded];
        } catch (\Firebase\JWT\ExpiredException $e) {
            return [false, 401, "Token has expired"];
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return 102;
            /*
            http_response_code(401);
            echo "Invalid signature";
            exit;
            */
        } catch (\Exception $e) {
            return [false, 400, "Invalid token"];
        }
    }
}
