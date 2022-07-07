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

class ForwardSlashNotAllowed extends ApiException
{
	public function __construct($code = 500, \Throwable $previous = null)
	{
		$message = 'Blobs stored in the root container can not have a name containing a forward slash (/).';

		parent::__construct($message, $code, $previous);
	}
}