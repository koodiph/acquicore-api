<?php

namespace Aquicore\API\PHP\Api\Exception;

/**
 * OAuth2.0 Aquicore exception handling
 */
class ClientException extends \Exception
{
	public $error_type;

	/**
	 * Make a new API Exception with the given result.
	 *
	 * @param $result
	 *   The result from the API server.
	 */
	public function __construct($code, $message, $error_type)
	{
		$this->error_type = $error_type;
		parent::__construct($message, $code);
	}
}
