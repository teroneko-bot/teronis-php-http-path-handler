<?php namespace Teronis\HttpPathHandler;

use Mabe\Enum;

class ParamType extends Enum {
    const GET = 1;
    const POST = 2;
    const HEADER_AUTHORIZATION = 4;
    const SHAREPOINT = 8;
    const FILES = 16;
}