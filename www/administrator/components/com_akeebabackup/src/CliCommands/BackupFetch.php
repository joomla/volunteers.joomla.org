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
use Akeeba\Component\AkeebaBackup\Administrator\Model\RemotefilesModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Session\SessionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:fetch
 *
 * Download a backup from the remote storage back to the server
 *
 * @since   7.5.0
 */
class BackupFetch extends AbstractCommand
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
	protected static $defaultName = 'akeeba:backup:fetch';

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

		$this->ioStyle->title(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_HEAD', $id));

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
		/** @var SessionInterface $session */
		$session   = $this->getApplication()->getSession();
		$profileId = $record['profile_id'];

		$session->set('akeebabackup.profile', $profileId);
		Platform::getInstance()->load_configuration($profileId);

		/** @var RemotefilesModel $model */
		$model     = $this->getMVCFactory()->createModel('Remotefiles', 'Administrator');
		$part      = 0;
		$frag      = 0;
		$totalSize = 0;
		$doneSize  = 0;

		$configuration = Factory::getConfiguration();
		$configuration->set('akeeba.tuning.max_exec_time', 1);
		$configuration->set('akeeba.tuning.run_time_bias', 10);

		$this->ioStyle->section(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_SECTION_DOWNLOADING', $id));

		$progress = $this->ioStyle->createProgressBar(1);

		$progress->display();

		while (true)
		{
			if ($totalSize > 0)
			{
				$progress->setMaxSteps($totalSize);
				$progress->setProgress($doneSize);

				$progress->setMessage(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_LBL_PART_FRAG', $part, $frag));
			}

			try
			{
				// Try downloading
				$result = $model->downloadToServer($id, $part, $frag);

				// Get the modified model state
				$id   = $model->getState('id');
				$part = $model->getState('part');
				$frag = $model->getState('frag');

				// Get session variables
				$totalSize = $session->get('akeebabackup.dl_totalsize', 0);
				$doneSize  = $session->get('akeebabackup.dl_donesize', 0);

				// Are we done yet?
				if (($part >= 0) && ($result === true))
				{
					$totalSize = max($totalSize, $doneSize);
					$progress->setMaxSteps($totalSize);
					$progress->setProgress($doneSize);
					$progress->finish();

					$this->ioStyle->newLine(2);

					$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_LBL_FINISHED', $id));

					return 0;
				}
			}
			catch (Exception $e)
			{
				$this->ioStyle->newLine(2);

				$errorMessage = $e->getMessage();
				$this->ioStyle->error([
					Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_ERR_FAILED', $id),
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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_OPT_ID'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_FETCH_HELP'));
	}
}
