<?php namespace Teronis\HttpPathHandler;

class SharePointKeyring extends ArrayKeyringBase {
    private $keyring;

    public function __construct() {
        $this->keyring = [];
    }

    protected function getArray(): array{
        return $this->keyring;
    }

    public function addKeyValuePair(string $key, $value) {
        $this->keyring[$key] = $value;
    }
}