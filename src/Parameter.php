<?php namespace Teronis\HttpPathHandler;

use MabeEnum\EnumSet;

class Parameter {
    public static function reto(Parameter $param): Parameter {
        return $param;
    }

    private const ALLOWED_TYPES = array(
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

    public static function create(string $paramName, $comparisonValue = null): Parameter {
        return new self($paramName, $comparisonValue);
    }

    private static function createParamTypeSet(): EnumSet {
        return new EnumSet(ParamType::class);
    }

    private $paramName;
    private $compareMode;
    private $comparisonValue;
    private $comparisonType;
    private $paramTypeSet;
    private $isOptional;
    private $isComparableNullValueValidatable;
    private $isSealed;

    // # > construction

    public function __construct(string $paramName, $comparisonValue = null) {
        $this->paramName = $paramName;
        $this->comparisonValue = $comparisonValue;

        if ($this->hasComparisonValue()) {
            $this->compareMode = ParamCompareMode::get(ParamCompareMode::VALUE_TYPE);
            $this->withComparisonTypeUnsafely(gettype($comparisonValue));
        } else {
            $this->compareMode = ParamCompareMode::get(ParamCompareMode::ONLY_NAME);
        }

        $this->paramTypeSet = new EnumSet(ParamType::class);
        $this->paramTypeSet->attach(ParamType::GET);
        $this->isOptional = false;
        $this->isComparableNullValueValidatable = false;
        $this->isSealed = false;
    }

    public function setCompareMode(int $compareMode) {
        $this->ensureSealedState(false);
        $typedCompareMode = ParamCompareMode::get($compareMode);
        $this->compareMode = $typedCompareMode;
    }

    public function withComparisonType(string $type): Parameter {
        $this->ensureSealedState(false);
        return $this->withComparisonTypeUnsafely($type);
    }

    private function withComparisonTypeUnsafely(string $type): Parameter {
        if (!$this->compareMode->is(ParamCompareMode::VALUE_TYPE)) {
            $this->compareMode = ParamCompareMode::get(ParamCompareMode::ONLY_TYPE);
        }

        if (!array_key_exists($type, self::ALLOWED_TYPES)) {
            throw self::getTypeNotFoundException();
        }

        if ($this->hasComparisonValue()) {
            if ($this->tryConvertArgumentValueWithTypeUnsafely($this->comparisonValue, $type)) {
                $this->comparisonType = $type;
            } else {
                throw new \Exception("The comparison value that has been specified at construction cannot be converted to type '$type'.");
            }
        } else {
            $this->comparisonType = $type;
        }

        return $this;
    }

    public function asParamType(int $paramType, ?int $paramType2 = null, ?int $paramTypeX = null): Parameter {
        $this->ensureSealedState(false);
        $this->paramTypeSet = self::createParamTypeSet();
        $args = func_get_args();

        while ($arg = current($args)) {
            if (is_null($arg)) {
                continue;
            }

            $this->paramTypeSet->attach($arg);
            next($args);
        }

        return $this;
    }

    public function isOptional(): Parameter {
        $this->ensureSealedState(false);
        $this->isOptional = true;
        return $this;
    }

    public function passComparableNullValueValidation(): Parameter {
        $this->ensureSealedState(false);
        $this->isComparableNullValueValidatable = true;
        return $this;
    }

    public function ensureSealedState(bool $state) {
        if ($state && !$this->isSealed) {
            throw new \Exception("You cannot call functions that rely on a sealed state.");
        } else if (!$state && $this->isSealed) {
            throw new \Exception("You cannot manipulate the class after the class has been saeled.");
        }
    }

    public function ensureCompareMode(int $compareMode) {
        if (!$this->compareMode->is($compareMode)) {
            throw new \Exception("The method you called does not support the compare mode " . $this->compareMode->getName() . ".");
        }
    }

    public function seal() {
        if (!$this->compareMode->is(ParamCompareMode::ONLY_NAME) && !$this->hasComparisonType()) {
            throw new \Exception("Comparison type has not been specified at creation.");
        }

        $this->isSealed = true;
    }

    // construction < #

    // # > variables

    public function getParamName(): string {
        return $this->paramName;
    }

    public function getCompareMode(): ParamCompareMode {
        return $this->compareMode;
    }

    public function getComparisonValue() {
        return $this->comparisonValue;
    }

    public function getComparisonType(): ?string {
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

    public function tryConvertArgumentValue(&$value) {
        $this->ensureSealedState(true);
        return $this->tryConvertArgumentValueUnsafely($value);
    }

    public function tryConvertArgumentValueUnsafely(&$value) {
        return $this->tryConvertArgumentValueWithTypeUnsafely($value, $this->getComparisonType());
    }

    public function tryConvertArgumentValueWithType(&$value, $type) {
        $this->ensureSealedState(true);
        $this->tryConvertArgumentValueWithTypeUnsafely($value, $type);
    }

    public function tryConvertArgumentValueWithTypeUnsafely(&$value, $type) {
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

    public function compareComparisonValueTo($comparableValue) {
        $this->ensureSealedState(true);
        $this->ensureCompareMode(ParamCompareMode::VALUE_TYPE);
        return $this->compareComparisonValueToUnsafely($comparableValue);
    }

    public function hasComparisonValue() {
        return !is_null($this->comparisonValue);
    }

    public function compareComparisonValueToUnsafely($comparableValue) {
        return $this->getComparisonValue() === $comparableValue;
    }

    public function isComparableTypeValid($httpVarValue): bool {
        $this->ensureSealedState(true);
        return $this->isComparableTypeValidUnsafely($httpVarValue);
    }

    public function hasComparisonType() {
        return !is_null($this->comparisonType);
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
