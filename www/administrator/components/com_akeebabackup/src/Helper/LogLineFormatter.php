<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die();

class LogLineFormatter
{
	/**
	 * Formats one raw `level|timestamp|message` log line as an HTML fragment for the log viewer.
	 *
	 * L21: the timestamp field is HTML-escaped alongside the message field. The engine's own writer neutralises the
	 * `|` delimiter inside the message, but this viewer must not assume every log file on disk was necessarily
	 * written by an unpatched engine, or written without the interleaving race described in Util/Logger.php.
	 *
	 * @param   string  $line  One raw line read from the log file, including its trailing newline.
	 *
	 * @return  string|null  The HTML fragment to echo, or null if the line does not have all three fields.
	 */
	public static function format(string $line): ?string
	{
		$exploded = explode('|', $line, 3);

		if (count($exploded) < 3)
		{
			return null;
		}

		switch (trim($exploded[0]))
		{
			case 'ERROR':
				$fmtString = '<span style="color: red; font-weight: bold;">[';
				break;
			case 'WARNING':
				$fmtString = '<span style="color: #D8AD00; font-weight: bold;">[';
				break;
			case 'INFO':
				$fmtString = '<span style="color: black;">[';
				break;
			case 'DEBUG':
				$fmtString = '<span style="color: #666666; font-size: small;">[';
				break;
			default:
				$fmtString = '<span style="font-size: small;">[';
				break;
		}

		return $fmtString . htmlspecialchars($exploded[1]) . '] ' . htmlspecialchars($exploded[2]) . '</span><br/>' . "\n";
	}
}
