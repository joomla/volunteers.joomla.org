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
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Akeeba\Component\AkeebaBackup\Administrator\Model\LogModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:log:get
 *
 * Retrieves log files known to Akeeba Backup
 *
 * @since   7.5.0
 */
class LogGet extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:log:get';

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

		$profile_id = max(1, (int) $this->cliInput->getArgument('profile_id') ?? 1);
		$log_tag    = (string) $this->cliInput->getArgument('log_tag') ?? 1;

		define('AKEEBA_PROFILE', $profile_id);

		/** @var LogModel $model */
		$model = $this->getMVCFactory()->createModel('Log', 'Administrator');
		$model->setState('tag', $log_tag);
		$model->echoRawLog(true);

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
		$this->addArgument('profile_id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_LOG_GET_OPT_PROFILE_ID'));
		$this->addArgument('log_tag', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_LOG_GET_OPT_LOG_TAG'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_LOG_GET_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_LOG_GET_HELP'));
	}
}
