<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Akeeba\Engine\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

#[\AllowDynamicProperties]
class LogModel extends BaseDatabaseModel
{
	/**
	 * Get an array with the names of all log files in this backup profile
	 *
	 * @param   bool  $onlyFailed  Should I only return the log files of backups marked as failed?
	 *
	 * @return  string[]
	 */
	public function getLogFiles(bool $onlyFailed = false): array
	{
		$configuration = Factory::getConfiguration();
		$outputDir     = $configuration->get('akeeba.basic.output_directory');

		$files = Factory::getFileLister()->getFiles($outputDir);
		$ret   = [];

		if (empty($files) || !is_array($files))
		{
			return $ret;
		}

		foreach ($files as $filename)
		{
			$baseName         = basename($filename);
			$startsWithAkeeba = substr($baseName, 0, 7) == 'akeeba.';
			$endsWithPhpLog   = substr($baseName, -8) == '.log.php';
			$endsWithLog      = !$endsWithPhpLog && substr($baseName, -4) == '.log';
			$endsWithPhp      = !$endsWithPhpLog && substr($baseName, -4) == '.php';
			$isDefaultLog     = in_array($baseName, ['akeeba.log', 'akeeba.log.php', 'akeeba.php']);

			if ($startsWithAkeeba && ($endsWithLog || $endsWithPhpLog || $endsWithPhp) && !$isDefaultLog)
			{
				/**
				 * Extract the tag from the filename (akeeba.tag.log, akeeba.tag.log.php or akeeba.tag.php)
				 *
				 * We ignore the first seven characters ("akeeba.") and the last X characters, where X is 8 if the
				 * log file name ends with .log.php or 4 if the log name ends with .log or .php.
				 */
				$tag = substr($baseName, 7, -($endsWithPhpLog ? 8 : 4));

				if (empty($tag))
				{
					continue;
				}

				$parts = explode('.', $tag);
				$key   = array_pop($parts);
				$key   = str_replace('id', '', $key);
				$key   = is_numeric($key) ? sprintf('%015u', $key) : $key;

				if (empty($parts))
				{
					$key = str_repeat('0', 15) . '.' . $key;
				}
				else
				{
					$key .= '.' . implode('.', $parts);
				}

				$ret[$key] = $tag;
			}
		}

		if ($onlyFailed)
		{
			$ret = $this->keepOnlyFailedLogs($ret);
		}

		krsort($ret);

		return $ret;
	}

	/**
	 * Gets the JHtml options list for selecting a log file
	 *
	 * @param   bool  $onlyFailed  Should I only return the log files of backups marked as failed?
	 *
	 * @return  array
	 */
	public function getLogList(bool $onlyFailed = false): array
	{
		$origin  = null;
		$options = [];

		$list = $this->getLogFiles($onlyFailed);

		if (!empty($list))
		{
			$options[] = HTMLHelper::_('select.option', null, Text::_('COM_AKEEBABACKUP_LOG_CHOOSE_FILE_VALUE'));

			foreach ($list as $item)
			{
				$text = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN_' . $item);

				if (strstr($item, '.') !== false)
				{
					[$origin, $backupId] = explode('.', $item, 2);

					$text = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN_' . $origin) . ' (' . $backupId . ')';
				}

				$options[] = HTMLHelper::_('select.option', $item, $text);
			}
		}

		return $options;
	}

	/**
	 * Output the raw text log file to the standard output without the PHP die header
	 *
	 * @param   bool  $withHeader  Should I include a header telling the user how to submit this file?
	 *
	 * @return  void
	 */
	public function echoRawLog($withHeader = true)
	{
		$tag     = $this->getState('tag', '');
		$logFile = Utils::getLogFilePath($tag);

		if ($withHeader)
		{
			echo "WARNING: Do not copy and paste lines from this file!\r\n";
			echo "You are supposed to ZIP and attach it in your support forum post.\r\n";
			echo "If you fail to do so, we will be unable to provide efficient support.\r\n";
			echo "\r\n";
			echo "--- START OF RAW LOG --\r\n";
		}

		// The at sign (silence operator) is necessary to prevent PHP showing a warning if the file doesn't exist or
		// isn't readable for any reason.
		$fp = is_null($logFile) ? false : @fopen($logFile, 'r');

		if ($fp === false)
		{
			if ($withHeader)
			{
				echo "--- END OF RAW LOG ---\r\n";
			}

			return;
		}

		$firstLine = @fgets($fp);
		if (substr($firstLine, 0, 5) != '<' . '?' . 'php')
		{
			@fclose($fp);
			@readfile($logFile);
		}
		else
		{
			while (!feof($fp))
			{
				echo rtrim(fgets($fp)) . "\r\n";
			}

			@fclose($fp);
		}

		if ($withHeader)
		{
			echo "--- END OF RAW LOG ---\r\n";
		}
	}

	protected function keepOnlyFailedLogs($logs)
	{
		$db            = $this->getDatabase();
		$query         = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
		                    ->select([
			                    $db->quoteName('tag'),
			                    $db->quoteName('backupid'),
		                    ])
		                    ->from($db->quoteName('#__akeebabackup_backups'))
		                    ->where($db->quoteName('status') . ' = ' . $db->quote('fail'));
		$failedBackups = $db->setQuery($query)->loadObjectList() ?: [];

		if (empty($failedBackups))
		{
			return [];

		}

		$failedBackups = array_map(function ($o) {
			$tag = $o->tag ?? '';

			return $tag . (empty($tag) ? '' : '.') . $o->backupid;
		}, $failedBackups);

		return array_intersect($logs, $failedBackups);
	}

}