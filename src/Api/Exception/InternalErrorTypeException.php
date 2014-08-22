<?php

namespace Aquicore\API\PHP\Api\Exception;

class InternalErrorTypeException extends ClientException
{
    function __construct($message)
    {
        parent::__construct(0, $message, INTERNAL_ERROR_TYPE);
    }
}
