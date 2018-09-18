<?php namespace Teronis\HttpPathHandler;

// use IHttpMethodKeyring;
// use Exception;

abstract class ArrayKeyringBase implements IHttpMethodKeyring {
    protected abstract function getArray(): array;

    public function hasHttpKey(string $paramName) {
        $array = $this->getArray();
        return !is_null($array) && array_key_exists($paramName, $array);
    }

    public function getHttpValue(string $paramName) {
        if (!$this->hasHttpKey($paramName)) {
            throw new \Exception("The value by key '" . $paramName . "' does not exist in keyring.");
        }

        return $this->getHttpValueUnsafely($paramName);
    }

    public function getHttpValueUnsafely($paramName) {
        return $this->getArray()[$paramName];
    }
}