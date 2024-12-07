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

class InvalidJSON extends Base
{
	public function __construct($message = "Invalid JSON data received", $code = '500', Exception $previous = null)
	{
		parent::__construct($message, (int) $code, $previous);
	}

}
