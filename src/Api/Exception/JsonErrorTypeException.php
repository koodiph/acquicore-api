<?php

namespace Aquicore\API\PHP\Api\Exception;

class JsonErrorTypeException extends ClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, JSON_ERROR_TYPE);
    }
}
