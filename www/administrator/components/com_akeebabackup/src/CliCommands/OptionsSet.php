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
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\JsonGuiDataParser;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:option:set
 *
 * Sets the value of a configuration option for an Akeeba Backup profile
 *
 * @since   7.5.0
 */
class OptionsSet extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use JsonGuiDataParser;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:option:set';

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

		$profileId = (int) ($this->cliInput->getOption('profile') ?? 1);

		define('AKEEBA_PROFILE', $profileId);

		/** @var ProfileModel $model */
		$model   = $this->getMVCFactory()->createModel('Profile', 'Administrator');
		$table   = $model->getTable();
		$didLoad = $table->load($profileId);

		if (!$didLoad)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_GET_ERR_INVALID_PROFILE', $profileId));

			return 1;
		}

		unset($table);
		unset($model);

		// Get the profile's configuration
		Platform::getInstance()->load_configuration($profileId);
		$config = Factory::getConfiguration();

		$key   = (string) $this->cliInput->getArgument('key') ?? '';
		$value = (string) $this->cliInput->getArgument('value') ?? '';

		// Get the key information from the GUI data
		$info = $this->parseJsonGuiData();

		// Does the key exist?
		if (!array_key_exists($key, $info['options']))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_GET_ERR_INVALID_KEY', $key));

			return 2;
		}

		// Validate / sanitize the value
		$optionInfo = $this->getOptionInfo($key, $info);

		switch ($optionInfo['type'])
		{
			case 'integer':
				$value = (int) $value;

				if (($value < $optionInfo['limits']['min']) || ($value > $optionInfo['limits']['max']))
				{
					$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_OUT_OF_BOUNDS', $value));

					return 3;
				}
				break;

			case 'bool':
				if (is_numeric($value))
				{
					$value = (int) $value;
				}
				elseif (is_string($value))
				{
					$value = strtolower($value);
				}

				if (in_array($value, [false, 0, '0', 'false', 'no', 'off'], true))
				{
					$value = 0;
				}
				elseif (in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true))
				{
					$value = 1;
				}
				else
				{
					$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_INVALID_BOOL', $value));

					return 3;
				}

				break;

			case 'enum':
				if (!in_array($value, $optionInfo['options']))
				{
					$options = array_map(function ($v) {
						return "'$v'";
					}, $optionInfo['options']);
					$options = implode(', ', $options);

					$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_INVALID_ENUM', $value, $options));

					return 3;
				}

				break;

			case 'hidden':
				$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_HIDDEN', $key));

				return 3;
				break;

			case 'string':
				break;

			default:
				$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_UNKNOWN_TYPE', $optionInfo['type'], $key));

				return 3;
				break;
		}

		$protected = $config->getProtectedKeys();
		$force     = $input->getOption('force');

		if (in_array($key, $protected) && !$force)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_PROTECTED', $key));

			return 4;
		}

		if (in_array($key, $protected) && $force)
		{
			$config->setKeyProtection($key, false);
		}

		$result = $config->set($key, $value, false);

		if ($result === false)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_ERR_GENERAL', $key));

			return 5;
		}

		Platform::getInstance()->save_configuration($profileId);

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_SET_LBL_SUCCESS', $key, $value));

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
		$this->addArgument('key', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_SET_OPT_KEY'));
		$this->addArgument('value', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_SET_OPT_VALUE'));
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_SET_OPT_PROFILE'), 1);
		$this->addOption('force', null, InputOption::VALUE_NONE, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_SET_OPT_FORCE'));

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_SET_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_SET_HELP'));
	}
}
