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
use Akeeba\Engine\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:log:list
 *
 * Lists log files known to Akeeba Backup
 *
 * @since   7.5.0
 */
class LogList extends AbstractCommand
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
	protected static $defaultName = 'akeeba:log:list';

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
		$format     = (string) ($this->cliInput->getOption('format') ?? 'table');

		define('AKEEBA_PROFILE', $profile_id);

		$configuration   = Factory::getConfiguration();
		$outputDirectory = $configuration->get('akeeba.basic.output_directory');

		if ($format === 'table')
		{
			$this->ioStyle->title(Text::sprintf('COM_AKEEBABACKUP_CLI_LOG_LIST_TITLE', $outputDirectory));
		}

		/** @var LogModel $model */
		$model = $this->getMVCFactory()->createModel('Log', 'Administrator');

		$outputData = array_map(function ($tag) use ($outputDirectory) {
			$possibilities = [
				$outputDirectory . '/akeeba.' . $tag . '.log',
				$outputDirectory . '/akeeba.' . $tag . '.log.php',
				$outputDirectory . '/akeeba.' . $tag . '.php',
				$outputDirectory . '/akeeba' . $tag . '.log',
				$outputDirectory . '/akeeba' . $tag . '.log.php',
				$outputDirectory . '/akeeba' . $tag . '.php',
			];

			$path = null;

			foreach ($possibilities as $possiblePath)
			{
				if (@is_file($possiblePath))
				{
					$path = $possiblePath;

					break;
				}
			}

			if (empty($path))
			{
				return null;
			}

			return [
				'tag'           => $tag,
				'absolute_path' => $path,
			];

		}, $model->getLogFiles());

		$outputData = array_filter($outputData, function ($x) {
			return !is_null($x);
		});

		return $this->printFormattedAndReturn(array_values($outputData), $format);
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
		$this->addArgument('profile_id', InputArgument::OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_LOG_LIST_OPT_PROFILE_ID'), 1);
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_LOG_LIST_OPT_FORMAT'), 'table');
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_LOG_LIST_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_LOG_LIST_HELP'));
	}
}
