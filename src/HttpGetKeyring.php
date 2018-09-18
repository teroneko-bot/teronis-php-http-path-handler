<?php namespace Teronis\HttpPathHandler;

// use ArrayKeyringBase;

class HttpGetKeyring extends ArrayKeyringBase {
    protected function getArray(): array
    {
        return $_GET;
    }
}