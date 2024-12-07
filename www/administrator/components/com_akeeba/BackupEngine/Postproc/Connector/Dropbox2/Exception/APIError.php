<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\Dropbox2\Exception;

defined('AKEEBAENGINE') || die();

use Exception;

class APIError extends Base
{
	/**
	 * APIError constructor.
	 *
	 * @param   string          $error             Short error code
	 * @param   string          $errorDescription  Long error description
	 * @param   int             $code              Numeric error ID (default: 500)
	 * @param   Exception|null  $previous          Previous exception
	 */
	public function __construct($error, $errorDescription, $code = 500, Exception $previous = null)
	{
		$message = "Error $error: $errorDescription";

		parent::__construct($message, (int) $code, $previous);
	}

}
