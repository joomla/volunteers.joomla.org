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

class MaxBlockSizeExceeded extends ApiException
{
	public function __construct(int $currentSize, int $maxSize, $code = 500, \Throwable $previous = null)
	{
		$currentMb = $currentSize / 1024 / 1024;
		$maxMb     = $maxSize / 1024 / 1024;
		$message   = sprintf('Cannot put a BLOB block larger than %0.0fMb. Current block size is %0.2f Mb', $maxMb, $currentMb);

		parent::__construct($message, $code, $previous);
	}
}