<?php

namespace App\Helpers;

use App\Services\HeaderService;

/**
 * Standard JSON response generator.
 *
 * Ensures that all endpoints use the same response envelope:
 *   - Success: {"status":"success","data":...}    (200/201)
 *   - Error:   {"status":"error","message":"..."} (400/401/404/409/422/500...)
 *
 * HTTP headers are applied through HeaderService; the output is written
 * using plain json_encode and the process is terminated. This prevents
 * a controller from accidentally continuing execution after sending
 * a response (eliminating the risk of duplicate output).
 */
class ResponseHelper
{
    /**
     * Successful operation response. Data is always carried under the "data" key.
     */
    public static function success(
        $_data = null,
        int $_code = 200,
        $message = null,
    ): void {
        $payload = ["status" => "success"];

        if ($_data !== null) {
            $payload["data"] = $_data;
        }

        if ($message !== null) {
            $payload["message"] = $message;
        }

        self::send($payload, $_code);
        return;
    }

    /**
     * Failed operation response.
     *
     * The "message" key always remains the summary message, while $_errors
     * optionally carries field-specific details (e.g. validation errors).
     * This additional key is intentionally included so that admin panels
     * and mobile clients can display form errors field by field.
     * It extends the existing "status/message" contract without breaking it.
     */
    public static function error(
        string $_message,
        int $_code = 400,
        ?array $_errors = null,
    ): void {
        $payload = ["status" => "error", "message" => $_message];

        if ($_errors !== null) {
            $payload["errors"] = $_errors;
        }

        self::send($payload, $_code);
    }

    /**
     * Common response handler: status code + headers + JSON output + termination.
     */
    private static function send(array $_payload, int $_code): void
    {
        http_response_code($_code);

        // JSON_UNESCAPED_UNICODE: Ensures that Turkish characters are
        // returned in a readable format instead of being escaped as \uXXXX.
        echo json_encode($_payload, JSON_UNESCAPED_UNICODE);
        exit();
    }
}
