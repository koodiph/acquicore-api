<?php

namespace Aquicore\API\PHP\Api\Exception;

use Aquicore\API\PHP\Common\ErrorType;

class ApiErrorTypeException extends ClientException
{
    public $http_code;
    public $http_message;
    public $result;

    function __construct($code, $message, $result)
    {
        $this->http_code    = $code;
        $this->http_message = $message;
        $this->result       = $result;
        if (isset($result['error']) && is_array($result['error']) && isset($result['error']['code']))
        {
            parent::__construct($result['error']['code'], $result['error']['message'], ErrorType::API_ERROR_TYPE);
        }
        else
        {
            parent::__construct($code, $message, ErrorType::API_ERROR_TYPE);
        }
    }
}

