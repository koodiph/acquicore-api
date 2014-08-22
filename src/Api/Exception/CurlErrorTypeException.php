<?php

namespace Aquicore\API\PHP\Api\Exception;

class CurlErrorTypeException extends ClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, CURL_ERROR_TYPE);
    }
}

