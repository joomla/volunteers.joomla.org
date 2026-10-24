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
use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:profile:create
 *
 * Creates a new Akeeba Backup profile
 *
 * @since   7.5.0
 */
class ProfileCreate extends AbstractCommand
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
	protected static $defaultName = 'akeeba:profile:create';

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

		$format = (string) $this->cliInput->getOption('format') ?? 'text';
		$format = in_array($format, ['text', 'json']) ? $format : 'text';

		/** @var ProfileModel $model */
		$model = $this->getMVCFactory()->createModel('Profile', 'Administrator');

		// Set up the new profile data
		$profileData = [
			'description'   => 'New backup profile',
			'quickicon'     => '1',
			'configuration' => '',
			'filters'       => '',
		];

		$description = (string) $this->cliInput->getOption('description') ?? 0;

		if (!is_null($description))
		{
			$profileData['description'] = trim($description);
		}

		$profileData['quickicon'] = ((bool) $this->cliInput->getOption('quickicon') ?? true) ? 1 : 0;
		$table                    = $model->getTable();

		try
		{
			$result = $table->save($profileData);
			$error  = null;
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

		if ($result === false)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_ERR_FAILED', $error));

			return 2;
		}

		/**
		 * Create a new profile configuration.
		 *
		 * Loading the new profile's empty configuration causes the Platform code to revert to the default options and
		 * save them automatically to the database.
		 */
		$profileId = $table->getId();
		Platform::getInstance()->load_configuration($profileId);

		if ($format == 'json')
		{
			echo json_encode($table->getId());

			return 0;
		}

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_LBL_SUCCESS', $table->getId()));

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
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_OPT_DESCRIPTION'), Text::_('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_LBL_NEW_PROFILE'));
		$this->addOption('quickicon', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_OPT_QUICKICON'), 1);
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_OPT_FORMAT'), 'text');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_CREATE_HELP'));
	}
}
