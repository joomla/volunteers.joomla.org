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
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\MemoryInfo;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\TimeInfo;
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:check
 *
 * CHeck for failed backups and sends emails about them
 *
 * @since   7.5.0
 */
class BackupCheck extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use MemoryInfo;
	use TimeInfo;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:check';

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

		$this->ioStyle->title(Text::_('COM_AKEEBABACKUP_CLI_HEAD_CHECK'));

		/** @var StatisticsModel $model */
		$model = $this->getMVCFactory()->createModel('Statistics', 'Administrator');
		$model->setStateSetFlag(true);

		$result = $model->notifyFailed();

		if ($result['result'])
		{
			$this->ioStyle->success($result['message']);

			return 0;
		}

		$this->ioStyle->warning($result['message']);

		return 1;
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
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_CHECK_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_CHECK_HELP'));
	}
}
