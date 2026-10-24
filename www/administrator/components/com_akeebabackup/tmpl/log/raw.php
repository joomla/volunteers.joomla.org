<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Joomla\CMS\Language\Text;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Log\RawView $this */

// -- Get the log's file name
$tag     = $this->tag;
$logFile = Utils::getLogFilePath($tag);

@ob_end_clean();

if (is_null($logFile))
{
	// Oops! The log doesn't exist!
	echo '<p>' . Text::_('COM_AKEEBABACKUP_LOG_ERROR_LOGFILENOTEXISTS') . '</p>';

	return;
}
else
{
	// Allright, let's load and render it
	$fp = fopen($logFile, "r");
	if ($fp === FALSE)
	{
		// Oops! The log isn't readable?!
		echo '<p>' . Text::_('COM_AKEEBABACKUP_LOG_ERROR_UNREADABLE') . '</p>';

		return;
	}

	while (!feof($fp))
	{
		$line = fgets($fp);
		if (!$line) return;
		$exploded = explode("|", $line, 3);
		unset($line);
		if (count($exploded) < 3) continue;
		switch (trim($exploded[0]))
		{
			case "ERROR":
				$fmtString = "<span style=\"color: red; font-weight: bold;\">[";
				break;
			case "WARNING":
				$fmtString = "<span style=\"color: #D8AD00; font-weight: bold;\">[";
				break;
			case "INFO":
				$fmtString = "<span style=\"color: black;\">[";
				break;
			case "DEBUG":
				$fmtString = "<span style=\"color: #666666; font-size: small;\">[";
				break;
			default:
				$fmtString = "<span style=\"font-size: small;\">[";
				break;
		}
		$fmtString .= $exploded[1] . "] " . htmlspecialchars($exploded[2]) . "</span><br/>\n";
		unset($exploded);
		echo $fmtString;
		unset($fmtString);
	}
}

@ob_start();
