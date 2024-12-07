<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Backup\Admin\Model\Backup;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use FOF40\Container\Container;
use Joomla\Console\Command\AbstractCommand;
use Akeeba\Backup\Admin\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Backup\Admin\CliCommands\MixIt\ConfigureIO;
use Akeeba\Backup\Admin\CliCommands\MixIt\MemoryInfo;
use Akeeba\Backup\Admin\CliCommands\MixIt\TimeInfo;
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
class BackupTake extends AbstractCommand
{
	use ConfigureIO, ArgumentUtilities, MemoryInfo, TimeInfo;

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

		$this->ioStyle->title('Taking a backup with Akeeba Backup');

		$mark        = microtime(true);
		$container   = Container::getInstance('com_akeeba', [], 'admin');
		$profile     = (int) ($this->cliInput->getOption('profile') ?? 1);
		$description = $this->cliInput->getOption('description') ?? '';
		$comment     = $this->cliInput->getOption('comment') ?? '';
		$overrides   = $this->commaListToMap($this->cliInput->getOption('overrides') ?? '');

		/** @var Backup $model */
		$model = $container->factory->model('Backup')->tmpInstance();

		if (empty($description))
		{
			$description = $model->getDefaultDescription() . ' (Joomla CLI)';
		}

		// Make sure $profile is a positive integer >= 1
		$profile = max(1, $profile);

		// Set the active profile
		$container->platform->setSessionVar('profile', $profile, 'akeeba');

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
				$this->ioStyle->section(sprintf("Starting backup using profile #%s.", $profile));

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
				sprintf("Last Tick   : %s", date('Y-m-d H:i:s \G\M\TO (T)')),
				sprintf("Domain      : %s", $array['Domain'] ?? ''),
				sprintf("Step        : %s", $array['Step'] ?? ''),
				sprintf("Substep     : %s", $array['Substep'] ?? ''),
				sprintf("Progress    : %1.2f%%", $array['Progress'] ?? 0.0),
				sprintf("Memory used : %s", $this->memUsage()),
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

		$this->ioStyle->comment(sprintf("Peak memory used : %s", $peakMemory));
		$this->ioStyle->comment(sprintf("Backup loop exited after %s", $elapsed));

		if (!empty($array['Error']))
		{
			$this->ioStyle->error($array['Error']);

			return 1;
		}

		if ($hasWarnings)
		{
			$this->ioStyle->success("The backup process is now complete with warnings.");

			return 2;
		}

		$this->ioStyle->success("The backup process is now complete.");

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
		$help = "<info>%command.name%</info> will take a backup with Akeeba Backup
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, 'Profile number');
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Short description for the backup record, accepts the standard Akeeba Backup archive naming variables');
		$this->addOption('comment', null, InputOption::VALUE_OPTIONAL, 'Longer comment for the backup record, provide it in HTML');
		$this->addOption('overrides', null, InputOption::VALUE_OPTIONAL, 'Set up configuration overrides in the format "key1=value1,key2=value2"');
		$this->setDescription('Take a backup with Akeeba Backup');
		$this->setHelp($help);
	}
}
