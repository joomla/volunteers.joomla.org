<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\AzureModern\Exception;

defined('AKEEBAENGINE') || die();

class InvalidContainerName extends ApiException
{
	public function __construct($code = 500, \Throwable $previous = null)
	{
		$message = 'Container name does not adhere to container naming conventions. See https://docs.microsoft.com/en-us/rest/api/storageservices/Naming-and-Referencing-Containers--Blobs--and-Metadata for more information.';

		parent::__construct($message, $code, $previous);
	}
}