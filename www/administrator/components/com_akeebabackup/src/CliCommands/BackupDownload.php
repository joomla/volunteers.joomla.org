<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ConfigureIO;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Countable;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:download
 *
 * Returns a backup archive part for a backup record known to Akeeba Backup
 *
 * @since   7.5.0
 */
class BackupDownload extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:download';

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   7.5.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureSymfonyIO($input, $output);

		try
		{
			$this->initialiseComponent($this->getApplication());
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error([
				Text::_('COM_AKEEBABACKUP_CLI_ERR_CANNOT_LOAD_BACKUP_ENGINGE'),
				$e->getMessage(),
			]);

			return 255;
		}

		$id      = (int) $this->cliInput->getArgument('id') ?? 0;
		$part    = (int) $this->cliInput->getArgument('part') ?? 0;
		$outFile = $this->cliInput->getOption('file');

		if (!empty($outFile))
		{
			$this->ioStyle->title(Text::sprintf('COM_AKEEBABACKUP_CLI_HEAD_BACKUP_DOWNLOAD', $part, $id));
		}

		if ($id <= 0)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));

			return 1;
		}

		$stat         = Platform::getInstance()->get_statistics($id);
		$allFileNames = Factory::getStatistics()->get_all_filenames($stat);

		if (empty($allFileNames))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DOWNLOAD_NO_FILES', $id));

			return 1;
		}

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		if (is_null($allFileNames))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DOWNLOAD_NO_FILES_MAYBE_REMOTE', $id));

			return 2;
		}

		if (($part >= (is_array($allFileNames) || $allFileNames instanceof Countable ? count($allFileNames) : 0)) || !isset($allFileNames[$part]))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DOWNLOAD_PART_OUT_OF_RANGE', $part, $id));

			return 3;
		}

		$fileName = $allFileNames[$part];

		if (!@file_exists($fileName))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DOWNLOAD_PART_FILE_MISSING', $part, $id));

			return 4;
		}

		$basename  = @basename($fileName);
		$fileSize  = @filesize($fileName);
		$extension = strtolower(str_replace(".", "", strrchr($fileName, ".")));

		if (empty($outFile))
		{
			readfile($fileName);

			return 0;
		}

		if (is_dir($outFile))
		{
			$outFile = rtrim($outFile, '/\\') . DIRECTORY_SEPARATOR . $basename;
		}
		else
		{
			$dotPos  = strrpos($outFile, '.');
			$outFile = ($dotPos === false) ? $outFile : (substr($outFile, 0, $dotPos) . '.' . $extension);
		}

		// Read in 1M chunks
		$blocksize = 1048576;
		$handle    = @fopen($fileName, "r");

		if ($handle === false)
		{
			$this->ioStyle->error(text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DOWNLOAD_PART_FILE_UNREADABLE', $fileName));

			return 5;
		}

		$fp = @fopen($outFile, 'w');

		if ($fp === false)
		{
			fclose($handle);

			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DOWNLOAD_PART_FILE_UNWRITEABLE', $outFile));

			return 6;
		}

		$progress    = $this->ioStyle->createProgressBar($fileSize);
		$runningSize = 0;
		$progress->display();

		while (!@feof($handle))
		{
			$data        = @fread($handle, $blocksize);
			$readLength  = strlen($data);
			$runningSize += $readLength;

			fwrite($fp, $data);

			$progress->setProgress($readLength);
		}

		$progress->finish();

		$this->ioStyle->newLine(2);

		@fclose($handle);
		@fclose($fp);

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_BACKUP_DOWNLOAD_DONE', $part, $id, $outFile));

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DOWNLOAD_OPT_ID'));
		$this->addArgument('part', InputArgument::OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DOWNLOAD_OPT_PART'));
		$this->addOption('file', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DOWNLOAD_OPT_FILE'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DOWNLOAD_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DOWNLOAD_HELP'));
	}
}
