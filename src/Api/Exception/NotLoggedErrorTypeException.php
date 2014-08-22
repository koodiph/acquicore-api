<?php

namespace Aquicore\API\PHP\Api\Exception;

class NotLoggedErrorTypeException extends ClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, NOT_LOGGED_ERROR_TYPE);
    }
}
