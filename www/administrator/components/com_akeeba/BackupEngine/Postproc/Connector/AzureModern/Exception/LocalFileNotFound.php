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

class LocalFileNotFound extends ApiException
{
	public function __construct($code = 500, \Throwable $previous = null)
	{
		$message = 'Local file not found.';

		parent::__construct($message, $code, $previous);
	}
}