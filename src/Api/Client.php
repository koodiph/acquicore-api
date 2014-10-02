<?php

namespace Aquicore\API\PHP\Api;

use Aquicore\API\PHP\Common\RestErrorCode;

/**
 * OAuth2.0 Aquicore client-side implementation.
 */
class Client
{
    /**
     * Array of persistent variables stored.
     */
    protected $conf = array();
    protected $refresh_token;
    protected $authToken;
    protected $version;

    /**
     * Default options for cURL.
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER         => TRUE,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'aquicoreclient',
        CURLOPT_SSL_VERIFYPEER => TRUE,
        CURLOPT_HTTPHEADER     => array('Accept: application/json'),
    );

    const BACKEND_BASE_URI = 'http://my.aquicore.com/api/v1';
    const BACKEND_AUTH_URI = 'http://my.aquicore.com/api/v1/session/login';

    /**
     * Initialize a NA OAuth2.0 Client.
     *
     * @param $config
     *   An associative array as below:
     *   - code: (optional) The authorization code.
     *   - username: (optional) The username.
     *   - password: (optional) The password.
     *   - client_id: (optional) The application ID.
     *   - client_secret: (optional) The application secret.
     *   - refresh_token: (optional) A stored refresh_token to use
     *   - authToken: (optional) A stored authToken to use
     *   - object_cb : (optionale) An object for which func_cb method will be applied if object_cb exists
     *   - func_cb : (optional) A method called back to store tokens in its context (session for instance)
     */
    public function __construct($config = array())
    {
        // If tokens are provided let's store it
        if (isset($config['authToken'])) {
            $this->authToken = $config['authToken'];
            unset($authToken);
        }
        if (isset($config['refresh_token'])) {
            $this->refresh_token = $config['refresh_token'];
        }

        // We must set uri first.
        $uri = array(
            'base_uri' => static::BACKEND_BASE_URI,
            'auth_uri' => static::BACKEND_AUTH_URI,
        );
        foreach ($uri as $key => $val) {
            if (isset($config[$key])) {
                $this->setVariable($key, $config[$key]);
                unset($config[$key]);
            } else {
                $this->setVariable($key, $val);
            }
        }

        // Other else configurations.
        foreach ($config as $name => $value) {
            $this->setVariable($name, $value);
        }

        if ($this->getVariable('code') == null && isset($_GET['code'])) {
            $this->setVariable('code', $_GET['code']);
        }
    }

    /**
     * Returns the current refresh token
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * Sets a persistent variable.
     *
     * To avoid problems, always use lower case for persistent variable names.
     *
     * @param $name
     *   The name of the variable to set.
     * @param $value
     *   The value to set.
     */
    public function setVariable($name, $value)
    {
        $this->conf[$name] = $value;
        return $this;
    }

    /**
     * Returns a persistent variable.
     *
     * To avoid problems, always use lower case for persistent variable names.
     *
     * @param $name
     *   The name of the variable to return.
     * @param $default
     *   The default value to use if this variable has never been set.
     *
     * @return
     *   The value of the variable.
     */
    public function getVariable($name, $default = NULL)
    {
        return isset($this->conf[$name]) ? $this->conf[$name] : $default;
    }

    private function setTokens($value)
    {
        if (isset($value['authToken'])) {
            $this->authToken = $value['authToken'];
            $update = true;
        }

        if (isset($value['refresh_token'])) {
            $this->refresh_token = $value['refresh_token'];
            $update = true;
        }

        if (isset($update)) {
            $this->updateSession();
        }
    }

    /**
     * Set token stored by application (in session generally) into this object
     */
    public function setTokensFromStore($value)
    {
        if (isset($value['authToken'])) {
            $this->authToken = $value['authToken'];
        }
        if (isset($value['refresh_token'])) {
            $this->refresh_token = $value['refresh_token'];
        }
    }

    public function unsetTokens()
    {
        $this->authToken     = null;
        $this->refresh_token = null;
    }

    private function updateSession()
    {
        $cb     = $this->getVariable('func_cb');
        $object = $this->getVariable('object_cb');

        if ($object && $cb) {
            if (method_exists($object, $cb)) {
                call_user_func_array(
                    array($object, $cb),
                    array(array(
                        'authToken'     => $this->authToken,
                        'refresh_token' => $this->refresh_token,
                    ))
                );
            }
        } elseif ($cb && is_callable($cb)) {
            call_user_func_array(
                $cb,
                array(array(
                    'authToken'     => $this->authToken,
                    'refresh_token' => $this->refresh_token,
                ))
            );
        }
    }

    /**
     * Makes an HTTP request
     *
     * This method can be overriden by subclasses if developers want to do
     * fancier things or use something other than cURL to make the request.
     *
     * @param $path   The target path, relative to base_path/service_uri or an absolute URI.
     * @param $method (optional) The HTTP method (default 'GET').
     * @param $params (optional The GET/POST parameters.
     * @param $ch     (optional) An initialized curl handle
     *
     * @return The json_decoded result or ClientException if pb happend
     */
    public function makeRequest($path, $method = 'GET', $params = array())
    {
        $ch   = curl_init();
        $opts = self::$CURL_OPTS;
        if ($params) {
            switch ($method) {
                case 'GET':
                    $path .= '?' . http_build_query($params, NULL, '&');
                    break;
                default:
                    $opts[CURLOPT_POSTFIELDS] = json_encode($params);
                    break;
            }
        }
        $opts[CURLOPT_URL] = $path;

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);

        $errno = curl_errno($ch);
        // CURLE_SSL_CACERT || CURLE_SSL_CACERT_BADFILE
        if ($errno == 60 || $errno == 77) {
            echo "WARNING ! SSL_VERIFICATION has been disabled since ssl error retrieved. ".
                "Please check your certificate http://curl.haxx.se/docs/sslcerts.html\n";
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $result = curl_exec($ch);
        }

        if ($result === FALSE) {
            $e = new Exception\CurlErrorTypeException(curl_errno($ch), curl_error($ch));
            curl_close($ch);
            throw $e;
        }

        curl_close($ch);

        // Split the HTTP response into header and body.
        list($headers, $body) = explode("\r\n\r\n", $result);
        $headers = explode("\r\n", $headers);

        //Only 2XX response are considered as a success
        if (strpos($headers[0], 'HTTP/1.1 2') !== FALSE) {
            $decode = json_decode($body, TRUE);

            if (!$decode) {
                if (preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches)) {
                    throw new Exception\JsonErrorTypeException($matches[1], $matches[2]);
                } else {
                    throw new Exception\JsonErrorTypeException(200, 'OK');
                }
            }

            return $decode;

        } else {

            if (!preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches)) {
                $matches = array('', 400, 'bad request');
            }

            $decode = json_decode($body, TRUE);

            if (!$decode) {
                throw new Exception\ApiErrorTypeException($matches[1], $matches[2], null);
            }

            throw new Exception\ApiErrorTypeException($matches[1], $matches[2], $decode);
        }
    }

    /**
     * Retrieve an access token following the best grant to recover it (order id : code, refresh_token, password)
     *
     * @return array A valid array containing at least an authToken as an index
     *
     * @throws InternalErrorTypeException If unable to retrieve an authToken
     */
    public function getAccessToken()
    {
        if ($this->authToken) {
            return array('authToken' => $this->authToken);
        }

        if ($this->getVariable('username') && $this->getVariable('password')) {
            return $this->getAccessTokenFromPassword(
                $this->getVariable('username'), $this->getVariable('password')
            );
        }

        throw new Exception\InternalErrorTypeException('No access token stored');
    }

    /**
     * Get access token from OAuth2.0 token endpoint with basic user
     * credentials.
     *
     * This function will only be activated if both username and password
     * are setup correctly.
     *
     * @param $username Username to be check with.
     * @param $password Password to be check with.
     * @return array A valid OAuth2.0 JSON decoded access token in associative array
     * @throws InternalErrorTypeException If unable to retrieve an authToken
     */
    private function getAccessTokenFromPassword($username, $password)
    {
        if (!$this->getVariable('auth_uri')) {
            throw new Exception\InternalErrorTypeException('missing args for getting password grant');
        }

        $response = $this->makeRequest(
            $this->getVariable('auth_uri'),
            'POST',
            array(
                'user'     => $username,
                'password' => $password,
            )
        );

        $this->setTokens($response);

        return $response;
    }

    /**
     * Make an OAuth2.0 Request.
     *
     * Automatically append "authToken" in query parameters
     *
     * @param $path
     *   The target path, relative to base_path/service_uri
     * @param $method
     *   (optional) The HTTP method (default 'GET').
     * @param $params
     *   (optional The GET/POST parameters.
     *
     * @return
     *   The JSON decoded response object.
     *
     * @throws OAuth2Exception
     */
    protected function makeOAuth2Request($path, $method = 'GET', $params = array(), $reget_token = true)
    {
        try {
            $res = $this->getAccessToken();
        } catch(Exception\ApiErrorTypeException $ex) {
            throw new Exception\NotLoggedErrorTypeException($ex->getCode(), $ex->getMessage());
        }

        $params['authToken'] = $res['authToken'];

        try {
            $res = $this->makeRequest($path, $method, $params);
        } catch(Exception\ApiErrorTypeException $ex) {
            if ($reget_token != true) {
                throw $ex;
            }
            switch ($ex->getCode()) {
                case RestErrorCode::INVALID_ACCESS_TOKEN:
                    // NO BREAK
                case RestErrorCode::ACCESS_TOKEN_EXPIRED:
                    //Ok token has expired let's retry once
                    if (!$this->refresh_token) {
                        throw $ex;
                    }
                    try {
                        $this->getAccessTokenFromRefreshToken();//exception will be thrown otherwise
                    } catch(\Exception $ex2) {
                        //Invalid refresh token TODO: Throw a special exception
                        throw $ex;
                    }
                    $res = $this->makeOAuth2Request($path, $method, $params, false);
                    break;
                    // RETHROW
                default:
                    throw $ex;
            }
        }

        return $res;
    }

    /**
     * Make an API call.
     *
     * Support both OAuth2.0 or normal GET/POST API call, with relative
     * or absolute URI.
     *
     * If no valid OAuth2.0 access token found in session object, this function
     * will automatically switch as normal remote API call without "authToken"
     * parameter.
     *
     * Assume server reply in JSON object and always decode during return. If
     * you hope to issue a raw query, please use makeRequest().
     *
     * @param $path
     *   The target path, relative to base_path/service_uri or an absolute URI.
     * @param $method
     *   (optional) The HTTP method (default 'GET').
     * @param $params
     *   (optional The GET/POST parameters.
     *
     * @return
     *   The JSON decoded body response object.
     *
     * @throws ClientException
     */
    public function api($path, $method = 'GET', $params = array(), $secure = false)
    {
        if (is_array($method) && empty($params)) {
            $params = $method;
            $method = 'GET';
        }

        // json_encode all params values that are not strings.
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                continue;
            }
            $params[$key] = json_encode($value);
        }

        $res = $this->makeOAuth2Request($this->getUri($path, array(), $secure), $method, $params);

        return isset($res['body']) ? $res['body'] : $res;
    }

    /**
     * Make a REST call to a Aquicore server that do not need authToken
     *
     * @param $path
     *   The target path, relative to base_path/service_uri or an absolute URI.
     * @param $method
     *   (optional) The HTTP method (default 'GET').
     * @param $params
     *   (optional The GET/POST parameters.
     *
     * @return
     *   The JSON decoded response object.
     *
     * @throws ClientException
     */
    public function noTokenApi($path, $method = 'GET', $params = array())
    {
        if (is_array($method) && empty($params)) {
            $params = $method;
            $method = 'GET';
        }

        // json_encode all params values that are not strings.
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                continue;
            }
            $params[$key] = json_encode($value);
        }

        return $this->makeRequest($path, $method, $params);
    }

    /**
     * Build the URL for given path and parameters.
     *
     * @param $path
     *   (optional) The path.
     * @param $params
     *   (optional) The query parameters in associative array.
     *
     * @return
     *   The URL for the given parameters.
     */
    protected function getUri($path = '', $params = array(), $secure = false)
    {
        $url = $this->getVariable('services_uri')
            ? $this->getVariable('services_uri')
            : $this->getVariable('base_uri');

        if ($secure == true) {
            $url = self::strReplaceOnce('http', 'https', $url);
        }

        if (!empty($path)) {
            if (substr($path, 0, 4) == 'http') {
                $url = $path;
            } elseif(substr($path, 0, 5) == 'https') {
                $url = $path;
            } else {
                $url = rtrim($url, '/') . '/' . ltrim($path, '/');
            }
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params, NULL, '&');
        }

        return $url;
    }

    public static function strReplaceOnce($str_pattern, $str_replacement, $string)
    {
        if (strpos($string, $str_pattern) !== false) {
            $occurrence = strpos($string, $str_pattern);
            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }
        return $string;
    }
}
