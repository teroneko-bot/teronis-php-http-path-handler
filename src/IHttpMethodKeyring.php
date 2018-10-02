<?php namespace Teronis\HttpPathHandler;

interface IHttpMethodKeyring {
    public function hasHttpKey(string $paramName);

    public function getHttpValue(string $paramName);

    public function getHttpValueUnsafely(string $paramName);
}