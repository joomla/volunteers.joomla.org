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
use Akeeba\Component\AkeebaBackup\Administrator\Model\UploadModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:upload
 *
 * Retry uploading a backup to the remote storage
 *
 * @since   7.5.0
 */
class BackupUpload extends AbstractCommand
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
	protected static $defaultName = 'akeeba:backup:upload';

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

		$id = (int) $this->cliInput->getArgument('id') ?? 0;

		$this->ioStyle->title(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_HEAD', $id));

		if ($id <= 0)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));

			return 1;
		}

		$record = Platform::getInstance()->get_statistics($id);

		if (!is_array($record))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));

			return 1;
		}

		// Set the correct profile ID
		$profileId = $record['profile_id'];
		$this->getApplication()->getSession()->set('akeebabackup.profile', $profileId);
		Platform::getInstance()->load_configuration($profileId);

		/** @var UploadModel $model */
		$model = $this->mvcFactory->createModel('Upload', 'Administrator');
		$part  = 0;
		$frag  = 0;

		$configuration = Factory::getConfiguration();
		$configuration->set('akeeba.tuning.max_exec_time', 1);
		$configuration->set('akeeba.tuning.run_time_bias', 10);

		while (true)
		{
			$this->ioStyle->writeln(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_LBL_PROGRESS', $id, $part, $frag));

			try
			{// Try uploading
				$result = $model->upload($id, $part, $frag);// Get the modified model state
				$id     = $model->getState('id');
				$part   = $model->getState('part');
				$frag   = $model->getState('frag');
				if (($part >= 0) && ($result === true))
				{
					$this->ioStyle->newLine(2);

					$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_LBL_COMPLETE', $id));

					return 0;
				}
			}
			catch (Exception $e)
			{
				$this->ioStyle->newLine(2);

				$errorMessage = $e->getMessage();
				$this->ioStyle->error([
					Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_ERR_FAILED', $id),
					$errorMessage
				]);

				return 2;
			}
		}
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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_OPT_ID'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_UPLOAD_HELP'));
	}
}
