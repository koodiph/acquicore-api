<?php

namespace Aquicore\API\PHP\Api\Exception;

use Aquicore\API\PHP\Common\ErrorType;

class JsonErrorTypeException extends ClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, ErrorType::JSON_ERROR_TYPE);
    }
}
