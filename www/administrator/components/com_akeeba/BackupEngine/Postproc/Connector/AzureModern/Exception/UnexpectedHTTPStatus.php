<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\AzureModern\Exception;

defined('AKEEBAENGINE') || die();

class UnexpectedHTTPStatus extends ApiException
{
	public function __construct($errNo = "500", $code = 0, \Throwable $previous = null)
	{
		$message = "Unexpected HTTP status $errNo";

		parent::__construct($message, (int) $errNo, $previous);
	}
}