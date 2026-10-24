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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:profile:delete
 *
 * Delete an Akeeba Backup profile
 *
 * @since   7.5.0
 */
class ProfileDelete extends AbstractCommand
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
	protected static $defaultName = 'akeeba:profile:delete';

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

		$id = (int) $this->cliInput->getArgument('id') ?? 0;

		if ($id === 1)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_ERR_DEFAULT'));
		}

		/** @var ProfileModel $model */
		$model = $this->mvcFactory->createModel('Profile', 'Administrator');
		$table = $model->getTable();

		if (!$table->load($id))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_ERR_NOTFOUND', $id));

			return 2;
		}

		try
		{
			$result = $table->delete($id);
			$error = null;
		}
		catch (\Exception $e)
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

		if (!$result)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_ERR_FAILED', $id, $error));

			return 3;
		}

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_LBL_SUCCESS', $table->getId()));

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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_OPT_ID'));

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_DELETE_HELP'));
	}
}
