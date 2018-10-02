<?php namespace Teronis\HttpPathHandler;

class PathException extends \Exception {
    private $pathClassName;

    public function __construct(PathBase $path, \Throwable $previous = NULL) {
        $this->pathClassName = get_class($path);
        $message = 'Ein Fehler in der "Path"-Klasse ' . $this->getPathClassName() . " trat auf.";
        parent::__construct($message, 0, $previous);
    }

    public function getPathClassName(): string {
        return $this->pathClassName;
    }
}