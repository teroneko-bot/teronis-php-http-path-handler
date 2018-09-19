<?php namespace Teronis\HttpPathHandler;

class Argument {
    public $param;
    public $value;

    public function __construct(Parameter $param, $value) {
        $this->param = $param;
        $this->value = $value;
    }
}