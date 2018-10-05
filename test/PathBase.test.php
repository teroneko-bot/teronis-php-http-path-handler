<?php
declare (strict_types = 1);

use PHPUnit\Framework\TestCase;
use Teronis\HttpPathHandler\Parameter;
use Teronis\HttpPathHandler\PathBase;

final class Path extends PathBase {
    public function defineParams() {
        $this->includeParam(Parameter::create("target", "test"));
        $this->includeParam(Parameter::create("test", "test")->isOptional());
        $this->includeParam(Parameter::create("validNumber")->withComparisonType("int"));
        $this->includeParam(Parameter::create("badNumber")->withComparisonType("int"));
        $this->includeParam(Parameter::create("optionalBadNumber")->withComparisonType("int")->isOptional());
    }

    public function handlePath(\stdClass $httpArgs) {}
}

final class PathBaseTest extends TestCase {
    function testGetParamsQuery() {
        $path = new Path();

        $array = [
            "target" => "noway",
            "validNumber" => 2,
            "badNumber" => ["anyvaluehere"],
            "optionalBadNumber" => ["anyvaluehere"],
        ];

        $this->assertEquals($path->buildPathQuery($array), "?target=test&validNumber=2&badNumber");
        $this->assertEquals($path->buildPathQuery($array, ["target"]), "?validNumber=2&badNumber");
    }
}