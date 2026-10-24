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
use Akeeba\Component\AkeebaBackup\Administrator\Model\BackupModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:take
 *
 * Takes a new backup using Akeeba Backup
 *
 * @since   7.5.0
 */
class BackupTake extends AbstractCommand implements DatabaseAwareInterface
{
	use ConfigureIO;
	use ArgumentUtilities;
	use MemoryInfo;
	use TimeInfo;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:take';

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

		$this->ioStyle->title(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_HEAD'));

		$mark        = microtime(true);
		$profile     = (int) ($this->cliInput->getOption('profile') ?? 1);
		$description = $this->cliInput->getOption('description') ?? '';
		$comment     = $this->cliInput->getOption('comment') ?? '';
		$overrides   = $this->commaListToMap($this->cliInput->getOption('overrides') ?? '');

		/** @var BackupModel $model */
		$model = $this->getMVCFactory()->createModel('Backup', 'Administrator');

		if (empty($description))
		{
			$description = $model->getDefaultDescription() . ' (Joomla CLI)';
		}

		// Make sure $profile is a positive integer >= 1
		$profile = max(1, $profile);

		// Set the active profile
		define('AKEEBA_PROFILE', $profile);

		/**
		 * DO NOT REMOVE!
		 *
		 * The Model will only try to load the configuration after nuking the factory. This causes Profile 1 to be
		 * loaded first. Then it figures out it needs to load a different profile and it does – but the protected keys
		 * are NOT replaced, meaning that certain configuration parameters are not replaced. Most notably, the chain.
		 * This causes backups to behave weirdly. So, DON'T REMOVE THIS UNLESS WE REFACTOR THE MODEL.
		 */
		Platform::getInstance()->load_configuration($profile);

		// Dummy array so that the loop iterates once
		$array = [
			'HasRun'       => 0,
			'Error'        => '',
			'cli_firstrun' => 1,
		];

		$model->setState('tag', AKEEBA_BACKUP_ORIGIN);
		$model->setState('description', $description);
		$model->setState('comment', $comment);
		// Otherwise the Engine doesn't set a backup ID
		$model->setState('backupid', null);

		$hasWarnings = false;

		// Set up a progress bar
		while (($array['HasRun'] != 1) && (empty($array['Error'])))
		{
			if (isset($array['cli_firstrun']) && $array['cli_firstrun'])
			{
				$this->ioStyle->section(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_SECTION_START', $profile));

				$array = $model->startBackup(array_merge([
					'akeeba.tuning.min_exec_time'           => 0,
					'akeeba.tuning.max_exec_time'           => 15,
					'akeeba.tuning.run_time_bias'           => 100,
					'akeeba.advanced.autoresume'            => 0,
					'akeeba.tuning.nobreak.beforelargefile' => 1,
					'akeeba.tuning.nobreak.afterlargefile'  => 1,
					'akeeba.tuning.nobreak.proactive'       => 1,
					'akeeba.tuning.nobreak.finalization'    => 1,
					'akeeba.tuning.settimelimit'            => 0,
					'akeeba.tuning.setmemlimit'             => 1,
					'akeeba.tuning.nobreak.domains'         => 0,
				], $overrides));
			}
			else
			{
				$this->ioStyle->section('Continuing the backup');

				$array = $model->stepBackup();
			}

			// Print the new progress bar and info
			$messages = [
				Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_CONSOLELOG_LASTTICK', date('Y-m-d H:i:s \G\M\TO (T)')),
				Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_CONSOLELOG_DOMAIN', $array['Domain'] ?? ''),
				Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_CONSOLELOG_STEP', $array['Step'] ?? ''),
				Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_CONSOLELOG_SUBSTEP', $array['Substep'] ?? ''),
				Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_CONSOLELOG_PROGRESS', $array['Progress'] ?? 0.0),
				Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_CONSOLELOG_MEMORY', $this->memUsage()),
			];

			// Output any warnings
			if (!empty($array['Warnings']))
			{
				$hasWarnings = true;
				$this->ioStyle->warning($array['Warnings']);
			}

			$this->ioStyle->writeln($messages);

			// Recycle the database connection to minimise problems with database timeouts
			$db = Factory::getDatabase();
			$db->close();
			$db->open();

			// Reset the backup timer
			Factory::getTimer()->resetTime();
		}

		$peakMemory = $this->peakMemUsage();
		$elapsed    = $this->timeAgo($mark, time(), '', false);

		$this->ioStyle->comment(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_LBL_PEAKMEM', $peakMemory));
		$this->ioStyle->comment(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_LBL_TOTALTILE', $elapsed));

		if (!empty($array['Error']))
		{
			$this->ioStyle->error($array['Error']);

			return 1;
		}

		if ($hasWarnings)
		{
			$this->ioStyle->success(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_LBL_COMPLETE_WITH_WARNINGS'));

			return 2;
		}

		$this->ioStyle->success(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_LBL_COMPLETE'));

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
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_OPT_PROFILE'));
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_OPT_DESCRIPTION'));
		$this->addOption('comment', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_OPT_COMMENT'));
		$this->addOption('overrides', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_OPT_OVERRIDES'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_TAKE_HELP'));
	}
}
