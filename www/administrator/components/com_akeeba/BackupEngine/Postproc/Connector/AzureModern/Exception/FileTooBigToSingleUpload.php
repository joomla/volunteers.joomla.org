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

class FileTooBigToSingleUpload extends ApiException
{
	public function __construct($code = 500, \Throwable $previous = null)
	{
		$message = 'The file is too big to upload directly. Please use a smaller Part Size for Split Archives or uncheck the Disable Chunked Upload option and retry taking a backup.';

		parent::__construct($message, $code, $previous);
	}
}