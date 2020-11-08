<?php

namespace EFxPHP;

use Exception;

class Filter
{
    const IS_EQUAL = "= %s";
    const IS_GREATER_THAN = "> %s";
    const IS_GREATER_THAN_OR_EQUAL = ">= %s";
    const IS_IN_ARRAY = "IN (%s)";
    const IS_LESS_THAN = "< %s";
    const IS_LESS_THAN_OR_EQUAL = "<= %s>";
    const IS_NOT_EQUAL = "!= %s";
    const IS_NOT_IN_ARRAY = "NOT IN (%s)";
    const IS_LIKE = "LIKE %s";

    private $availableOperators =
    [
        "IS_EQUAL" => self::IS_EQUAL,
        "IS_GREATER_THAN" => self::IS_GREATER_THAN,
        "IS_GREATER_THAN_OR_EQUAL" => self::IS_GREATER_THAN_OR_EQUAL,
        "IS_IN_ARRAY" => self::IS_IN_ARRAY,
        "IS_LESS_THAN" => self::IS_LESS_THAN,
        "IS_LESS_THAN_OR_EQUAL" => self::IS_LESS_THAN_OR_EQUAL,
        "IS_NOT_EQUAL" => self::IS_NOT_EQUAL,
        "IS_NOT_IN_ARRAY" => self::IS_NOT_IN_ARRAY,
        "IS_LIKE" => self::IS_LIKE
    ];

    private $field;
    private $value;
    private $operator;

    /**
     * Filter constructor.
     * @param string $field
     * @param mixed $value
     * @param string $operator
     * @throws Exception
     */
    public function __construct(string $field, $value, $operator = self::IS_EQUAL) {
        $this->field = $field;
        $this->value = $value;
        $this->operator = $operator;

        if (!in_array($this->operator, $this->availableOperators))
            throw new Exception("Operator $this->operator is not available!");

        switch ($this->operator) {
            case self::IS_IN_ARRAY:
            case self::IS_NOT_IN_ARRAY:
                if (!is_array($this->value)) {
                    throw new Exception(
                        "The operator " .
                        array_search($this->operator, $this->availableOperators) .
                        " accepts only arrays as given value. " .
                        ucfirst(gettype($this->value)) .
                        " provided."
                    );
                }

                break;
            default:
                if (is_array($this->value) || is_object($this->value)) {
                    throw new Exception(
                        "The operator " .
                        array_search($this->operator, $this->availableOperators) .
                        " accepts only primitive types as given value. " .
                        ucfirst(gettype($this->value)).
                        " provided."
                    );
                }

                break;
        }
    }

    /**
     * Build the query filter.
     * @return string
     */
    public function buildFilterQuery() {
        $field = $this->field;
        $value = $this->value;
        $operator = $this->operator;

        if (is_string($this->value))
            $value = "'$value'";

        $result = "$field $operator ";

        $result = sprintf($result, $value);

        return $result;
    }
}