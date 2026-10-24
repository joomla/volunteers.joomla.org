<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ComponentOptions;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ConfigureIO;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:sysconfig:list
 *
 * Lists the Akeeba Backup component-wide options
 *
 * @since   7.5.0
 */
class SysconfigList extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use ComponentOptions;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:sysconfig:list';

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

		$format = (string) $this->cliInput->getOption('format') ?? 'table';
		$output = $this->getComponentOptions();

		if (in_array($format, ['table', 'csv']))
		{
			$temp = [];

			foreach ($output as $k => $v)
			{
				$temp[] = [
					'key'   => $k,
					'value' => $v,
				];
			}

			$output = $temp;
		}

		return $this->printFormattedAndReturn($output, $format);
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
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_LIST_OPT_FORMAT'), 'table');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_LIST_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_LIST_HELP'));
	}
}
