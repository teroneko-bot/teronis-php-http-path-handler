<?php namespace Teronis\HttpPathHandler;

// use Parameter;

class Argument {
    public $param;
    public $value;

    public function __construct(Parameter $param, $value) {
        $this->param = $param;
        $this->value = $value;
    }
}