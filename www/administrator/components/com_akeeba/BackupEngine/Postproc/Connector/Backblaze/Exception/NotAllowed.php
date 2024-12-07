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

class NotAllowed extends Base
{
	public function __construct($errorDescription, $code = '500', Exception $previous = null)
	{
		$message = "The following action is not allowed by the Backblaze B2 Application Key you have provided: $errorDescription";

		parent::__construct($message, (int) $code, $previous);
	}

}
