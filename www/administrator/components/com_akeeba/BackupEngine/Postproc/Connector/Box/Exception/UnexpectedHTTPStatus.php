<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\Box\Exception;

defined('AKEEBAENGINE') || die();

use Exception;

class UnexpectedHTTPStatus extends Base
{
	public function __construct($errNo = "500", $code = 0, Exception $previous = null)
	{
		$message = "Unexpected HTTP status $errNo";

		parent::__construct($message, (int) $errNo, $previous);
	}

}
