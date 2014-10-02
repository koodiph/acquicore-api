<?php

namespace Aquicore\API\PHP\Api\Exception;

use Aquicore\API\PHP\Common\ErrorType;

class InternalErrorTypeException extends ClientException
{
    function __construct($message)
    {
        parent::__construct(0, $message, ErrorType::INTERNAL_ERROR_TYPE);
    }
}
