<?php namespace Teronis\HttpPathHandler;

class Argument
{
    private $sharePoint;
    public $param;
    public $value;

    public function __construct(\stdClass $sharePoint, Parameter $param, $value)
    {
        $this->sharePoint = $sharePoint;
        $this->param = $param;
        $this->value = $value;
    }

    public function hasAnyHttpKey(&$outFirstKeyring = null, ?array $paramTypeValueIntersection = null)
    {
        return $this->sharePoint->pathManager->hasAnyHttpKey($this->param, $outFirstKeyring, $paramTypeValueIntersection);
    }
}
