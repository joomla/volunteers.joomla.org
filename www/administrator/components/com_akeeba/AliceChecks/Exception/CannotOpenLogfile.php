<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Exception;

use Joomla\CMS\Language\Text;
use RuntimeException;
use Throwable;

/**
 * ALICE Exception: cannot open log file
 */
class CannotOpenLogfile extends RuntimeException
{
	public function __construct($logFile, Throwable $previous = null)
	{
		$message = Text::sprintf('COM_AKEEBA_ALICE_ERR_CANNOT_OPEN_LOGFILE', $logFile);

		parent::__construct($message, 500, $previous);
	}
}