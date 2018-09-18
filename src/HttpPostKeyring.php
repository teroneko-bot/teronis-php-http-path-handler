<?php namespace Teronis\HttpPathHandler;

// use ArrayKeyringBase;

class HttpPostKeyring extends ArrayKeyringBase {
    protected function getArray(): array
    {
        return $_POST;
    }
}