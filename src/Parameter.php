<?php namespace Teronis\HttpPathHandler;

use MabeEnum\EnumSet;

class Parameter {
    const SEALED_EXCEPTION_TYPE_MESSAGE = "You cannot change the type after the class has been saeled.";

    const ALLOWED_TYPES = array(
        "bool" => "Parameter::isBool",
        "boolean" => "Parameter::isBool",
        "int" => "is_numeric",
        "integer" => "is_numeric",
        "float" => "is_numeric",
        "string" => "is_string",
        "array" => "is_array",
    );

    private static function isBool($value) {
        return (is_string($value) && ((is_numeric($value) && settype($value, "int") && ($value === 1 || $value === 0)))
            || (($value === "true" || $value === "false") || ($value === "yes" || $value === "no") || ($value === "on" || $value === "off")));
    }

    // used by array_filter as callback
    private static function isArrayValueFalse($value) {
        return $value !== "";
    }

    private static function getTypeNotFoundException(): \Exception {
        return new \Exception("The given type must be one of the types " . join("/", array_keys(self::ALLOWED_TYPES)));
    }

    public static function create(string $paramName, $comparisonValue = NULL): Parameter {
        return new self($paramName, $comparisonValue);
    }

    private static function createParamTypeSet(): EnumSet {
        return new EnumSet(ParamType::class);
    }

    private $paramName;
    private $comparisonValue;
    private $comparisonType;
    private $paramTypeSet;
    private $isOptional;
    private $isComparableNullValueValidatable;
    private $isSealed;

    // # > construction

    private function __construct(string $paramName, $comparisonValue = NULL) {
        $this->paramName = $paramName;
        $this->comparisonValue = $comparisonValue;

        if ($this->hasComparisonValue()) {
            $this->withComparisonTypeUnsafely(gettype($comparisonValue));
        }

        $this->paramTypeSet = new EnumSet(ParamType::class);
        $this->paramTypeSet->attach(ParamType::GET);
        $this->isOptional = false;
        $this->isComparableNullValueValidatable = false;
        $this->isSealed = false;
    }

    public function withComparisonType(string $type): Parameter {
        $this->ensureUnsealedState();
        return $this->withComparisonTypeUnsafely($type);
    }

    private function withComparisonTypeUnsafely(string $type): Parameter {
        $this->ensureUnsealedState();

        if (!array_key_exists($type, self::ALLOWED_TYPES)) {
            throw self::getTypeNotFoundException();
        }

        if ($this->hasComparisonValue()) {
            if ($this->convertArgumentValueWithTypeUnsafely($this->comparisonValue, $type)) {
                $this->comparisonType = $type;
            } else {
                throw new \Exception("The value cannot be converted to type '$type'.");
            }
        } else {
            $this->comparisonType = $type;
        }

        return $this;
    }

    public function asParamType(int $paramType): Parameter {
        $this->ensureUnsealedState();
        $this->paramTypeSet = self::createParamTypeSet();
        $args = func_get_args();

        while ($arg = current($args)) {
            $this->paramTypeSet->attach($arg);
            next($args);
        }

        return $this;
    }

    public function isOptional(): Parameter {
        $this->ensureUnsealedState();
        $this->isOptional = true;
        return $this;
    }

    public function passComparableNullValueValidation(): Parameter {
        $this->ensureUnsealedState();
        $this->isComparableNullValueValidatable = true;
        return $this;
    }

    private function ensureUnsealedState() {
        if ($this->isSealed) {
            throw new \Exception(self::SEALED_EXCEPTION_TYPE_MESSAGE);
        }
    }

    public function seal() {
        $this->isSealed = true;
    }

    // construction < #

    // # > variables

    public function getParamName(): string {
        return $this->paramName;
    }

    public function getComparisonValue() {
        return $this->comparisonValue;
    }

    public function getComparisonType(): string {
        return $this->comparisonType;
    }

    public function getParamTypeSet(): EnumSet {
        return $this->paramTypeSet;
    }

    public function getIsOptional(): bool {
        return $this->isOptional;
    }

    public function getIsSealed(): bool {
        return $this->isSealed;
    }

    public function getIsComparableNullValueValidatable(): bool {
        return $this->isComparableNullValueValidatable;
    }

    // variables < #

    // # > validation

    public function hasComparisonValue() {
        return !is_null($this->comparisonValue);
    }

    public function convertArgumentValue(&$value) {
        if ($this->isComparableTypeValid($value)) {
            $this->convertArgumentValueUnsafely($value);
        } else {
            throw new \UnexpectedValueException("Argument value was not convertable to " . $this->comparisonType . ".");
        }
    }

    public function convertArgumentValueUnsafely(&$value) {
        return $this->convertArgumentValueWithTypeUnsafely($value, $this->getComparisonType());
    }

    public function convertArgumentValueWithTypeUnsafely(&$value, $type) {
        if ("array" === $this->comparisonType) {
            if (!is_array($value)) {
                $value = [$value];
            }

            $value = array_filter($value, "self::isArrayValueFalse");
            // an array remains an array, so let's return
            return true;
        } else if ("bool" === $this->comparisonType) {
            $value = filter_var($value);
        }

        return settype($value, $type);
    }

    public function compareComparisonValueTo($value) {
        if ($this->hasComparisonValue()) {
            return $this->compareComparisonValueToUnsafely($value);
        } else {
            throw new \Exception("Comparison value has not been specified at creation.");
        }
    }

    public function compareComparisonValueToUnsafely($value) {
        return $this->getComparisonValue() === $value;
    }

    public function hasComparisonType() {
        return !is_null($this->comparisonType);
    }

    public function isComparableTypeValid($httpVarValue): bool {
        if ($this->hasComparisonType()) {
            return $this->isComparableTypeValidUnsafely($httpVarValue);
        } else {
            throw new \Exception("Comparison type has not been specified at creation.");
        }
    }

    public function isComparableTypeValidUnsafely($httpVarValue): bool {
        if ($this->isComparableNullValueValidatable) {
            $isComparisonTypeAnArray = "array" === $this->comparisonType;
            $isComparableNullValueValidated = (is_array($httpVarValue) ? [] === array_filter($httpVarValue, "self::isArrayValueFalse") : true) || is_null($httpVarValue) || $httpVarValue === "";
        } else {
            $isComparableNullValueValidated = false;
        }

        if ($isComparableNullValueValidated) {
            return true;
        } else {
            return (Parameter::ALLOWED_TYPES[$this->getComparisonType()])($httpVarValue);
        }
    }

    // # < validation
}