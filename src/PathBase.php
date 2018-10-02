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
        array_push($this->params, $param);
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
                    $param->convertArgumentValueUnsafely($httpVarValue);
                }

                if (!(!$param->hasComparisonValue() || ($param->compareComparisonValueToUnsafely($httpVarValue)))) {
                    return;
                }
            } else {
                $httpVarValue = NULL;
            }

            $args->{$paramName} = new Argument($sharePoint, $param, $httpVarValue);
        }

        $this->handlePath($args);
    }

    public function getIsSealed(): bool {
        return $this->isSealed;
    }

    public abstract function handlePath(\stdClass $args);
}