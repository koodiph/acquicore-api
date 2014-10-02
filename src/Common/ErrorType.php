<?php

namespace Aquicore\API\PHP\Common;

class ErrorType
{
    const CURL_ERROR_TYPE       = 0;
    const API_ERROR_TYPE        = 1; //error return from api
    const INTERNAL_ERROR_TYPE   = 2; //error because internal state is not consistent
    const JSON_ERROR_TYPE       = 3;
    const NOT_LOGGED_ERROR_TYPE = 4; //unable to get access token
}
