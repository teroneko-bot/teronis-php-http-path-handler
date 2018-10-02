<?php namespace Teronis\HttpPathHandler;

class HttpGetKeyring extends ArrayKeyringBase {
    protected function getArray(): array
    {
        return $_GET;
    }
}