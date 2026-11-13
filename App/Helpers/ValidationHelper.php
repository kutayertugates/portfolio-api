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
    public const TYPE_STRING = "string";
    public const TYPE_INT = "int";
    public const TYPE_FLOAT = "float";

    /** Raw input to be validated (output of json_decode). */
    private array $input;

    /** Field rules: field => ['required' => bool, 'default' => mixed, 'type' => string, 'checks' => [[Closure, string], ...]]. */
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
            $this->fields[$_field] = [
                "required" => false,
                "default" => null,
                "type" => self::TYPE_STRING,
                "checks" => [],
            ];
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
     * Sets a fallback value when the field is missing or empty.
     */
    public function default(mixed $_value): self
    {
        $this->fields[$this->current_field]["default"] = $_value;
        return $this;
    }

    /**
     * Sets the field's data type for validation and normalization.
     */
    public function type(string $_type): self
    {
        if (
            !in_array(
                $_type,
                [self::TYPE_STRING, self::TYPE_INT, self::TYPE_FLOAT],
                true,
            )
        ) {
            throw new \InvalidArgumentException(
                "Unsupported field type: " . $_type,
            );
        }

        $this->fields[$this->current_field]["type"] = $_type;
        return $this;
    }

    /**
     * Requires the value to have at least the specified number of characters.
     */
    public function minLength(int $_min): self
    {
        return $this->addCheck(function ($_value, $_raw) use ($_min) {
            return strlen($_value) >= $_min;
        }, "Bu alan en az " . $_min . " karakter uzunluğunda olmalıdır.");
    }

    /**
     * Requires the value to have no more than the specified number of characters.
     */
    public function maxLength(int $_max): self
    {
        return $this->addCheck(function ($_value, $_raw) use ($_max) {
            return strlen($_value) <= $_max;
        }, "Bu alan en fazla " . $_max . " karakter uzunluğunda olmalıdır.");
    }

    /**
     * Requires the value to be a valid email address.
     */
    public function email(): self
    {
        return $this->addCheck(function ($_value, $_raw) {
            return filter_var($_value, FILTER_VALIDATE_EMAIL) !== false;
        }, "Bu alan geçerli bir e-posta adresi olmalıdır.");
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
     * Values are normalized according to their specified field types.
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
                $raw_value !== null &&
                (!is_string($raw_value) || trim($raw_value) !== "");

            if (!$is_present) {
                // Missing/empty field: use default value or null.
                $this->validated[$field_name] = $rules["default"];

                if ($rules["required"]) {
                    $this->errors[$field_name] = "Bu alan zorunludur.";
                }

                continue;
            }

            // Validate and normalize according to the specified type.
            switch ($rules["type"]) {
                case self::TYPE_STRING:
                    if (
                        !is_string($raw_value) &&
                        !is_int($raw_value) &&
                        !is_float($raw_value)
                    ) {
                        $this->errors[$field_name] =
                            "Bu alan bir metin olmalıdır.";
                        continue 2;
                    }

                    $normalized = trim((string) $raw_value);
                    break;

                case self::TYPE_INT:
                    if (filter_var($raw_value, FILTER_VALIDATE_INT) === false) {
                        $this->errors[$field_name] =
                            "Bu alan bir tam sayı olmalıdır.";
                        continue 2;
                    }

                    $normalized = (int) $raw_value;
                    break;

                case self::TYPE_FLOAT:
                    if (!is_numeric($raw_value)) {
                        $this->errors[$field_name] =
                            "Bu alan sayısal bir değer olmalıdır.";
                        continue 2;
                    }

                    $normalized = (float) $raw_value;
                    break;
            }

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
