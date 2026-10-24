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
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:delete
 *
 * Deletes a backup record known to Akeeba Backup, or just its files
 *
 * @since   7.5.0
 */
class BackupDelete extends AbstractCommand
{
	use MVCFactoryAwareTrait;
	use ConfigureIO;
	use ArgumentUtilities;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:delete';

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

		$id        = (int) $this->cliInput->getArgument('id') ?? 0;
		$onlyFiles = $this->cliInput->getOption('only-files');

		$this->ioStyle->title(Text::sprintf('COM_AKEEBABACKUP_CLI_HEAD_BACKUP_DELETE', $id));

		if ($id <= 0)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));

			return 1;
		}

		/** @var StatisticModel $model */
		$model = $this->getMVCFactory()->createModel('Statistic', 'Administrator');
		$ids = [$id];

		try
		{
			$model->setState('workaround.override_canDelete', true);

			if ($onlyFiles)
			{
				$model->deleteFiles($ids);

				$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_BACKUP_DELETE_FILES', $id));

				return 0;
			}

			$model->delete($ids);

			$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_LBL_BACKUP_DELETE', $id));

		}
		catch (RuntimeException $e)
		{
			if ($onlyFiles)
			{
				$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DELETE_FILES', $id, $e->getMessage()));
			}
			else
			{
				$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_ERR_BACKUP_DELETE', $id, $e->getMessage()));
			}

			return 1;
		}

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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DELETE_OPT_ID'));
		$this->addOption('only-files', null, InputOption::VALUE_NONE, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DELETE_OPT_ONLY_FILES'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DELETE_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_DELETE_HELP'));
	}
}
