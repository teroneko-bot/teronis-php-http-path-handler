<?php namespace Teronis\HttpPathHandler;

// use IHttpMethodKeyring;

class JsonHttpPostKeyring implements IHttpMethodKeyring {
    private $isPrepared;
    private $deJsonObj;

    public function __construct() {
        $this->isPrepared = false;
    }

    public function getIsPrepared(): bool {
        return $this->isPrepared;
    }

    public function hasHttpKey(string $paramName) {
        $this->tryPrepare();
        // json does not allow null as key, so we check by isset
        return !is_null($this->deJsonObj) && property_exists($this->deJsonObj, $paramName);
    }

    public function getHttpValue(string $paramName) {
        $this->tryPrepare();

        if (!$this->hasHttpKey($paramName)) {
            throw new \Exception("A value by property '" . $paramName . "' does not exist in json object.");
        }

        return $this->getHttpValueUnsafely($paramName);
    }

    public function getHttpValueUnsafely($paramName) {
        $httpParamValue = $this->deJsonObj->{$paramName};
        return $httpParamValue;
    }

    private function tryPrepare() {
        if ($this->getIsPrepared()) {
            return;
        }

        $this->isPrepared = true;
        $jsonContent = file_get_contents("php://input");
        $this->deJsonObj = json_decode($jsonContent);
    }
}