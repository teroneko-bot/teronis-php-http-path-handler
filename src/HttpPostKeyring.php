<?php namespace Teronis\HttpPathHandler;

class HttpPostKeyring extends ArrayKeyringBase {
    protected function getArray(): array
    {
        return $_POST;
    }
}