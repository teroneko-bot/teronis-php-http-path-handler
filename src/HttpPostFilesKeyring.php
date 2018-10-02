<?php namespace Teronis\HttpPathHandler;

class HttpPostFilesKeyring extends ArrayKeyringBase {
    protected function getArray(): array
    {
        return $_FILES;
    }
}