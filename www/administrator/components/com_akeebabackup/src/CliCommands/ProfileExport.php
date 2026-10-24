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
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Component\AkeebaBackup\Administrator\Table\ProfileTable;
use Akeeba\Engine\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:profile:export
 *
 * Exports an Akeeba Backup profile as a JSON string.
 *
 * @since   7.5.0
 */
class ProfileExport extends AbstractCommand
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
	protected static $defaultName = 'akeeba:profile:export';

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

		$id      = (int) $this->cliInput->getArgument('id') ?? 0;
		$filters = (bool) $this->cliInput->getOption('filters') ?? false;

		/** @var ProfileModel $model */
		$model = $this->mvcFactory->createModel('Profile', 'Administrator');
		/** @var ProfileTable $table */
		$table = $model->getTable();

		if (!$table->load($id))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_EXPORT_ERR_NOT_FOUND', $id));

			return 1;
		}

		$data = $table->getProperties();

		if (!$filters)
		{
			unset($data['filters']);
		}

		unset($data['id']);

		// Decrypt configuration data if necessary
		if (substr($data['configuration'], 0, 12) == '###AES128###')
		{
			// Load the server key file if necessary
			$key = Factory::getSecureSettings()->getKey();

			$data['configuration'] = Factory::getSecureSettings()->decryptSettings($data['configuration'], $key);
		}

		echo json_encode($data);

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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_EXPORT_OPT_ID'));
		$this->addOption('filters', null, InputOption::VALUE_NONE, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_EXPORT_OPT_FILTERS'));

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_EXPORT_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_EXPORT_HELP'));
	}
}
