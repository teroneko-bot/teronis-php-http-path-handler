<?php namespace Teronis\HttpPathHandler;

class AuthorizationHttpHeadKeyring implements IHttpMethodKeyring {
    private $isPrepared;
    private $headerAuthType;
    private $headerAuthValue;

    public function __construct() {
        $this->isPrepared = false;
    }

    public function getIsPrepared(): bool {
        return $this->isPrepared;
    }

    public function hasHttpKey(string $paramName) {
        $this->tryPrepare();
        return !is_null($this->headerAuthType) && $this->headerAuthType === $paramName;
    }

    public function getHttpValue(string $paramName) {
        $this->tryPrepare();

        if (!$this->hasHttpKey($paramName)) {
            throw new \Exception("The Authorization type is '$this->headerAuthType', but '$paramName' was expected.");
        }

        return $this->headerAuthValue;
    }

    public function getHttpValueUnsafely($paramName) {
        return $this->headerAuthValue;
    }

    private function tryPrepare() {
        if ($this->getIsPrepared()) {
            return;
        }

        $this->isPrepared = true;

        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            $headerAuthContent = trim($_SERVER["HTTP_AUTHORIZATION"]);
            $parts = explode(" ", $headerAuthContent);

            if (count($parts) == 2) {
                $this->headerAuthType = $parts[0];
                $this->headerAuthValue = $parts[1];
            }
        }
    }
}