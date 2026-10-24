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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:sysconfig:get
 *
 * Gets the value of an Akeeba Backup component-wide option
 *
 * @since   7.5.0
 */
class SysconfigGet extends AbstractCommand
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
	protected static $defaultName = 'akeeba:sysconfig:get';

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

		$key     = (string) $this->cliInput->getArgument('key') ?? '';
		$format  = (string) $this->cliInput->getOption('format') ?? 'table';
		$options = $this->getComponentOptions();

		if (!array_key_exists($key, $options))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_SYSCONFIG_GET_ERR_CANNOT_FIND', $key));

			return 1;
		}

		$value = $options[$key] ?? '';

		switch ($format)
		{
			case 'text':
			default:
				echo $value;
				break;

			case 'json':
				echo json_encode($value);
				break;

			case 'print_r':
				print_r($value);
				break;

			case 'var_dump':
				var_dump($value);
				break;

			case 'var_export':
				var_export($value);
				break;
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
		$this->addArgument('key', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_GET_OPT_KEY'));
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_GET_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_GET_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_GET_HELP'));
	}
}
