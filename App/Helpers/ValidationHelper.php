<?php

namespace App\Helpers;

/**
 * Field-based request validator.
 *
 * Reduces dozens of repetitive if-else validation blocks in controllers
 * to concise, chainable rules and collects all validation errors
 * in a single place.
 *
 * Responsibility boundary: This class only handles the "rule execution
 * mechanism" (required fields, length, format, etc.). Which fields are
 * subject to which rules (business logic) is defined in the Controller.
 * Database-dependent checks (e.g. uniqueness) belong in the Controller,
 * not here, because such decisions are part of the business workflow
 * and must be evaluated alongside database constraints.
 */
class ValidationHelper
{
    /** Raw input to be validated (output of json_decode). */
    private array $input;

    /** Field rules: field => ['required' => bool, 'checks' => [[Closure, string], ...]]. */
    private array $fields = [];

    /** Validation results: field => first error message. */
    private array $errors = [];

    /** Validated and normalized values: field => value. */
    private array $validated = [];

    /** Ensures that validation rules are executed only once (lazy execution). */
    private bool $ran = false;

    /** The field currently targeted by the rule definition chain. */
    private ?string $current_field = null;

    public function __construct(array $_input)
    {
        $this->input = $_input;
    }

    /**
     * Selects the field to validate and starts the fluent rule definition chain.
     */
    public function field(string $_field): self
    {
        if (!isset($this->fields[$_field])) {
            $this->fields[$_field] = ["required" => false, "checks" => []];
        }

        $this->current_field = $_field;
        return $this;
    }

    /**
     * Requires the field to be present and non-empty (not null or whitespace-only).
     */
    public function required(): self
    {
        $this->fields[$this->current_field]["required"] = true;
        return $this;
    }

    /**
     * Requires the value to be textual (string/int/float).
     * Numeric values received as JSON numbers (e.g. student IDs)
     * are converted to strings; arrays, objects, and booleans are rejected.
     */
    public function string(): self
    {
        return $this->addCheck(function ($_value, $_raw) {
            return is_string($_raw) || is_int($_raw) || is_float($_raw);
        }, "This field must be a string.");
    }

    public function minLength(int $_min): self
    {
        return $this->addCheck(function ($_value, $_raw) use ($_min) {
            return strlen($_value) >= $_min;
        }, "This field must be at least " . $_min . " characters long.");
    }

    public function maxLength(int $_max): self
    {
        return $this->addCheck(function ($_value, $_raw) use ($_max) {
            return strlen($_value) <= $_max;
        }, "This field must not exceed " . $_max . " characters.");
    }

    public function email(): self
    {
        return $this->addCheck(function ($_value, $_raw) {
            return filter_var($_value, FILTER_VALIDATE_EMAIL) !== false;
        }, "Please enter a valid email address.");
    }

    /**
     * General-purpose regex validation for custom format rules.
     */
    public function regex(string $_pattern, string $_message): self
    {
        return $this->addCheck(function ($_value, $_raw) use ($_pattern) {
            return preg_match($_pattern, $_value) === 1;
        }, $_message);
    }

    /**
     * Returns whether any validation errors occurred.
     */
    public function fails(): bool
    {
        $this->run();
        return $this->errors !== [];
    }

    /**
     * Returns the first error message for each field (field => message).
     */
    public function getErrors(): array
    {
        $this->run();
        return $this->errors;
    }

    /**
     * Returns the validated values.
     * Values are trimmed and normalized to strings, so the Controller
     * does not need to sanitize the input again.
     */
    public function validated(): array
    {
        $this->run();
        return $this->validated;
    }

    private function addCheck(\Closure $_check, string $_message): self
    {
        $this->fields[$this->current_field]["checks"][] = [$_check, $_message];
        return $this;
    }

    /**
     * Executes all rules field by field.
     * Only the first error for each field is stored, allowing both
     * a summary message and field-specific details to be returned
     * to the client in a single response.
     */
    private function run(): void
    {
        if ($this->ran) {
            return;
        }
        $this->ran = true;

        foreach ($this->fields as $field_name => $rules) {
            $raw_value = $this->input[$field_name] ?? null;
            $is_present =
                $raw_value !== null && trim((string) $raw_value) !== "";

            if (!$is_present) {
                // Missing/empty field: store null if optional, otherwise add an error.
                $this->validated[$field_name] = null;
                if ($rules["required"]) {
                    $this->errors[$field_name] = "This field is required.";
                }
                continue;
            }

            // is_scalar prevents string cast warnings for array/object inputs.
            $normalized = is_scalar($raw_value)
                ? trim((string) $raw_value)
                : "";

            foreach ($rules["checks"] as [$check, $message]) {
                if (!$check($normalized, $raw_value)) {
                    $this->errors[$field_name] = $message;
                    break;
                }
            }

            $this->validated[$field_name] = $normalized;
        }
    }
}
