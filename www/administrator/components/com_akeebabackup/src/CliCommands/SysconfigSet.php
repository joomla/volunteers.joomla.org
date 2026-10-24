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
use Akeeba\Component\AkeebaBackup\Administrator\Helper\SecretWord;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:sysconfig:set
 *
 * Sets the value of an Akeeba Backup component-wide option
 *
 * @since   7.5.0
 */
class SysconfigSet extends AbstractCommand
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
	 * @since  7.5.0
	 * @var    string
	 */
	protected static $defaultName = 'akeeba:sysconfig:set';

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   7.5.0
	 */
	protected function configure(): void
	{
		$this->addArgument('key', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_SET_OPT_KEY'));
		$this->addArgument('value', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_SET_OPT_VALUE'));
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_SET_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_SET_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_SYSCONFIG_SET_HELP'));
	}

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
		$value   = (string) $this->cliInput->getArgument('value') ?? '';
		$options = $this->getComponentOptions();

		if (!array_key_exists($key, $options))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_SYSCONFIG_GET_ERR_CANNOT_FIND', $key));

			return 1;
		}

		if ((string) $options[$key] === $value)
		{
			return 0;
		}

		$cParams = ComponentHelper::getParams('com_akeebabackup');
		$cParams->set($key, $value);

		$this->getComponentObject($this->getApplication())
		     ->getComponentParametersService()
		     ->save($cParams);

		// Make sure the front-end backup Secret Word is stored encrypted
		SecretWord::enforceEncryption($cParams, 'frontend_secret_word');

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_SYSCONFIG_SET_LBL_SETTING', $key, $value));

		return 0;
	}
}
