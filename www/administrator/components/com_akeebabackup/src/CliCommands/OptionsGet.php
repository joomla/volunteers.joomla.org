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
 * akeeba:option:get
 *
 * Gets the value of a configuration option for an Akeeba Backup profile
 *
 * @since   7.5.0
 */
class OptionsGet extends AbstractCommand
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
	protected static $defaultName = 'akeeba:option:get';

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

		$format = (string) $this->cliInput->getOption('format') ?? 'text';

		/** @var ProfileModel $model */
		$model = $this->getMVCFactory()->createModel('Profile', 'Administrator');
		$table = $model->getTable();
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
		$value = $config->get($key, null, false);

		if (!is_null($value) && !is_scalar($value))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_GET_ERR_NO_MULTIPLE', $key, $key));

			return 2;
		}

		if (is_null($value))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_GET_ERR_INVALID_KEY', $key));

			return 3;
		}

		switch ($format)
		{
			case 'text':
			default:
				echo $value . PHP_EOL;
				break;

			case 'json':
				echo json_encode($value) . PHP_EOL;
				break;

			case 'print_r':
				print_r($value);
				echo PHP_EOL;
				break;

			case 'var_dump':
				var_dump($value);
				echo PHP_EOL;
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
		$this->addArgument('key', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_GET_OPT_KEY'));
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_GET_OPT_PROFILE'), 1);
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_GET_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_GET_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_GET_HELP'));
	}
}
