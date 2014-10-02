<?php

namespace Aquicore\API\PHP\Api\Exception;

use Aquicore\API\PHP\Common\ErrorType;

class NotLoggedErrorTypeException extends ClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, ErrorType::NOT_LOGGED_ERROR_TYPE);
    }
}
