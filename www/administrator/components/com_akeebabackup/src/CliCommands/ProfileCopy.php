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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:profile:copy
 *
 * Creates a copy of an Akeeba Backup profile
 *
 * @since   7.5.0
 */
class ProfileCopy extends AbstractCommand
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
	protected static $defaultName = 'akeeba:profile:copy';

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

		$format      = (string) $this->cliInput->getOption('format') ?? 'text';
		$format      = in_array($format, ['text', 'json']) ? $format : 'text';
		$id          = (int) $this->cliInput->getArgument('id') ?? 0;
		$withFilters = (bool) $this->cliInput->getOption('filters') ?? false;

		/** @var ProfileModel $model */
		$model = $this->getMVCFactory()->createModel('Profile', 'Administrator');
		$item  = $model->getItem($id);

		if ($item === false)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_COPY_ERR_NOT_FOUND', $id));

			return 1;
		}

		$profileData = $item->getProperties();
		unset($profileData['id']);

		if (!$withFilters)
		{
			$profileData['filters'] = '';
		}

		$description = (string) $this->cliInput->getOption('description') ?? 0;

		if (!is_null($description))
		{
			$profileData['description'] = trim($description);
		}

		$profileData['quickicon'] = (bool) $this->cliInput->getOption('quickicon') ?? $profileData['quickicon'];

		try
		{
			$table  = $model->getTable();
			$result = $table->save($profileData);
			$error  = null;
		}
		catch (\Throwable $e)
		{
			$result = false;
			$error  = $e->getMessage();
		}

		if ($result === false && $error === null)
		{
			/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
			/** @noinspection PhpDeprecationInspection */
			$error = $table->getError();
		}

		if ($result === false)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_COPY_ERR_FAILED', $id, $error));

			return 2;
		}

		if ($format == 'json')
		{
			echo json_encode($table->getId());

			return 0;
		}

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_COPY_LBL_SUCCESS', $table->getId()));

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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_OPT_ID'));
		$this->addOption('filters', null, InputOption::VALUE_NONE, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_OPT_FILTERS'));
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_OPT_DESCRIPTION'), null);
		$this->addOption('quickicon', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_OPT_QUICKICON'), null);
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_COPY_HELP'));
	}
}
