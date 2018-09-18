<?php namespace Teronis\HttpPathHandler;

// use ArrayKeyringBase;

class HttpPostFilesKeyring extends ArrayKeyringBase {
    protected function getArray(): array
    {
        return $_FILES;
    }
}