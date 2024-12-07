<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\Backblaze\Exception;

defined('AKEEBAENGINE') || die();

use Exception;

class cURLError extends Base
{
	public function __construct($errNo = "500", $code = '', Exception $previous = null)
	{
		$message = "cURL error $errNo: $code";

		parent::__construct($message, (int) $errNo, $previous);
	}

}
