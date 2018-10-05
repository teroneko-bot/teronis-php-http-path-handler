<?php namespace Teronis\HttpPathHandler;

abstract class PathBase {
    private $params;
    private $isSealed;

    public function __construct() {
        $this->params = [];
        $this->defineParams();
        $this->isSealed = false;
    }

    protected function defineParams() {
        return;
    }

    protected function includeParam(Parameter $param) {
        $param->seal();
        $this->params[$param->getParamName()] = $param;
    }

    /**
     * Create an URL-query. Only simple value types (int, string, ...) are supported.
     *
     * @param array $paramNameValuePairs Simple paramName => paramValue items are expected.
     * @return string
     */
    public function createPathQuery(?array $paramNameValuePairs = null): string {
        $string = "";

        foreach ($this->params as $param) {
            $param = Parameter::reto($param);
            $paramName = $param->getParamName();
            $compareMode = $param->getCompareMode();
            $isOptional = $param->getIsOptional();
            $hasNameValuePair = isset($paramNameValuePairs[$paramName]);
            $paramValue = null;

            // Skip the parameter, if parameter is optional
            // and no name value pair has been found.
            if ($isOptional && !$hasNameValuePair) {
                continue;
            } else if ($compareMode->is(ParamCompareMode::VALUE_TYPE)) {
                if (!$isOptional || $hasNameValuePair) {
                    $paramValue = $param->getComparisonValue();
                }
            }
            // As the parameter has no value to compare against,
            // we can look for value passed as name value pair.
            else if ($compareMode->is(ParamCompareMode::ONLY_TYPE)) {
                if ($hasNameValuePair) {
                    $_paramValue = $paramNameValuePairs[$paramName];
                } else {
                    // We assume an empty string, because a only name
                    // url parameter has an empty string as value.
                    $_paramValue = "";
                }

                // TODO: Implement transition from value types and array to string in Parameter class.

                try {
                    $valueWasConvertible = settype($_paramValue, "string");
                } catch (\Throwable $error) {
                    $valueWasConvertible = false;
                }

                // If the convert was not successful and the parameter
                // is not optional, then skip this parameter.
                if (!$valueWasConvertible && $isOptional) {
                    continue;
                }

                // If the convert was successful replace param value by temp value;
                if ($valueWasConvertible) {
                    $paramValue = $_paramValue;
                }
            }

            if ($string) {
                $string = $string . "&";
            }

            $string = $string . $paramName;

            // Check if value is truthy because an empty string cannot be represented in the query.
            if ($paramValue) {
                $string = $string . "=" . $paramValue;
            }
        }

        if ($string) {
            $string = "?" . $string;
        }

        return $string;
    }

    public function tryHandlePath($sharePoint) {
        $pathManager = $sharePoint->pathManager;
        $args = new \stdClass;
        $args->{SHAREPOINT} = $sharePoint;

        foreach ($this->params as $param) {
            if (!$pathManager->hasAnyHttpMethodKeyring($param->getParamTypeSet(), $paramTypeValueIntersection)) {
                return;
            }

            $paramName = $param->getParamName();
            $hasHttpKey = $pathManager->hasAnyHttpKey($param, $firstKeyring, $paramTypeValueIntersection);
            $paramIsOptional = $param->getIsOptional();

            if (!$hasHttpKey && !$paramIsOptional) {
                return;
            } else if ($hasHttpKey) {
                $httpVarValue = $firstKeyring->getHttpValueUnsafely($paramName);
                $paramHasComparisonType = $param->hasComparisonType();

                // move forward if param has no comparison type or if it can pass the type comparison
                if (!(!$paramHasComparisonType || $param->isComparableTypeValidUnsafely($httpVarValue))) {
                    $isReturnCanceled = false;

                    // otherwise look here before return
                    if (is_array($httpVarValue)) {
                        if (array_key_exists($sharePoint->handlePathesRun, $httpVarValue)) {
                            $httpVarValue = $httpVarValue[$sharePoint->handlePathesRun];

                            if ($param->isComparableTypeValidUnsafely($httpVarValue)) {
                                $isReturnCanceled = true;
                            }
                        }
                    }

                    if (!$isReturnCanceled) {
                        return;
                    }
                }

                if ($paramHasComparisonType) {
                    $param->tryConvertArgumentValueUnsafely($httpVarValue);
                }

                if (!(!$param->hasComparisonValue() || ($param->compareComparisonValueToUnsafely($httpVarValue)))) {
                    return;
                }
            } else {
                $httpVarValue = null;
            }

            $args->{$paramName} = new Argument($sharePoint, $param, $httpVarValue);
        }

        $this->handlePath($args);
    }

    public function getIsSealed(): bool {
        return $this->isSealed;
    }

    abstract public function handlePath(\stdClass $args);
}
