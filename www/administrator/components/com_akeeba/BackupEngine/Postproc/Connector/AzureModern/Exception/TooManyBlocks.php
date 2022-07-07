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

class TooManyBlocks extends ApiException
{
	public function __construct($code = 500, \Throwable $previous = null)
	{
		$message = 'Too many BLOB blocks. Azure supports up to 50,000 committed blocks. Upload finalisation failed.';

		parent::__construct($message, $code, $previous);
	}
}