<?php namespace Teronis\HttpPathHandler;

use MabeEnum\Enum;

class ParamCompareMode extends Enum {
    const ONLY_NAME = 0;
    const VALUE_TYPE = 1;
    const ONLY_TYPE = 2;
}
