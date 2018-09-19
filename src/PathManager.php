<?php namespace Teronis\HttpPathHandler;

use MabeEnum\EnumSet;

class PathManager {
    private static function getHttpPostKeyring(): ?IHttpMethodKeyring {
        if (array_key_exists("CONTENT_TYPE", $_SERVER)) {
            $contentPart = $_SERVER["CONTENT_TYPE"];

            if (strpos($contentPart, "multipart/form-data") !== false || strpos($contentPart, "application/x-www-form-urlencoded") !== false) {
                return new HttpPostKeyring();
            } else if (strpos($contentPart, "application/json") !== false) {
                return new JsonHttpPostKeyring();
            }
        }

        return NULL;
    }

    private static function getDefaultSharePoint(PathManager $pathManager): \stdClass{
        // default share point
        $sharePoint = new \stdClass();
        $sharePoint->skipTokenValidation = false;
        $sharePoint->tokenValidationSkipReason = NULL;
        $sharePoint->nextHandlePathesRun = false;
        // keyring
        $spKeyring = new SharePointKeyring();
        $pathManager->httpMethodKeyrings[ParamType::SHAREPOINT] = $spKeyring;
        $sharePoint->keyring = $spKeyring;
        // amount of runs entering the main handlePathes-function
        $sharePoint->handlePathesRun = 0;
        // returning share point
        return $sharePoint;
    }

    private $pathes;
    private $httpMethodKeyrings;

    public function __construct() {
        $this->pathes = array();

        $this->httpMethodKeyrings = [
            ParamType::GET => new HttpGetKeyring(),
            ParamType::HEADER_AUTHORIZATION => new AuthorizationHttpHeadKeyring(),
        ];

        $httpPostKeyring = self::getHttpPostKeyring();

        if (!is_null($httpPostKeyring)) {
            $this->httpMethodKeyrings[ParamType::POST] = $httpPostKeyring;
            $this->httpMethodKeyrings[ParamType::FILES] = new HttpPostFilesKeyring();
        }
    }

    // key value pairs
    public function registerPath(PathBase $path) {
        array_push($this->pathes, $path);
    }

    public function handlePathesWithErrorHandling() {
        try {
            $pathManager->handlePathes();
        } catch (\Exception $error) {
            $obj = getErrorResponseObject(REASON_EXCEPTION, "Eine Ausnahme ist aufgetreten.");
            $exceptions = [];
            $lastError = $error;

            do {
                $lastErrorObj = getErrorObject($lastError);

                if ($lastError instanceof \PathException) {
                    attachPathExceptionInfos($lastErrorObj, $lastError);
                }

                if ($lastError instanceof \PDOException) {
                    attachPDOExceptionInfos($lastErrorObj, $lastError);
                }

                array_push($exceptions, $lastErrorObj);
            } while (!is_null($lastError = $lastError->getPrevious()));

            $obj->exceptions = $exceptions;
            printResponseObjectAsJSON($obj);
        }
    }

    public function handlePathes(?\stdClass $sharePoint = NULL) {
        if (!is_null($sharePoint)) {
            // increase by one
            $sharePoint->handlePathesRun++;
            // prevent loop by reset
            $sharePoint->nextHandlePathesRun = false;
        } else {
            // instantiate default share point
            $sharePoint = self::getDefaultSharePoint($this);
        }

        foreach ($this->pathes as $path) {
            $sharePoint->name = get_class($path);
            $sharePoint->pathMng = $this;

            try {
                $path->tryHandlePath($sharePoint);

                if ($sharePoint->nextHandlePathesRun) {
                    break;
                }
            } catch (\Exception $error) {
                throw new PathException($path, $error);
            }
        }

        // while is preferred due StackOverflowException/recursion
        while ($sharePoint->nextHandlePathesRun) {
            $this->handlePathes($sharePoint);
        }
    }

    public function hasAnyHttpMethodKeyring(EnumSet $paramTypeSet, ?array &$paramTypeValueIntersection = NULL): bool {
        $first = $paramTypeSet->getValues();
        $keyrings = $this->httpMethodKeyrings;
        $second = array_keys($keyrings);
        $paramTypeValueIntersection = array_intersect($first, $second);
        $isNotEmpty = !empty($paramTypeValueIntersection);
        return $isNotEmpty;
    }

    public function hasAnyHttpKey(Parameter $param, &$firstKeyring = NULL, ?array $paramTypeValueIntersection = NULL) {
        $paramTypeSet = $param->getParamTypeSet();
        $hasAnyKeyring = false;

        if (is_null($paramTypeValueIntersection)) {
            $hasAnyKeyring = $this->hasAnyHttpMethodKeyring($paramTypeSet, $paramTypeValueIntersection);
        } else {
            $hasAnyKeyring = !empty($paramTypeValueIntersection);
        }

        if ($hasAnyKeyring) {
            $paramName = $param->getParamName();

            while ($paramTypeValue = current($paramTypeValueIntersection)) {
                $keyring = $this->httpMethodKeyrings[$paramTypeValue];

                if ($keyring->hasHttpKey($paramName)) {
                    $firstKeyring = $keyring;
                    return true;
                }

                next($paramTypeValueIntersection);
            }
        }

        return false;
    }
}