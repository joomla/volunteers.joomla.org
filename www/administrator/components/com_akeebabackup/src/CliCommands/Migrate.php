<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands;

use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\ConfigureIO;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

defined('_JEXEC') or die;

class Migrate extends \Joomla\Console\Command\AbstractCommand
{
	use MVCFactoryAwareTrait;
	use ConfigureIO;
	use ArgumentUtilities;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  9.0.0
	 */
	protected static $defaultName = 'akeeba:migrate';

	/**
	 * @inheritDoc
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

		$this->ioStyle->title(Text::_('COM_AKEEBABACKUP_CLI_HEAD_MIGRATE'));

		// Is the Akeeba Backup 8 component installed?
		$hasAkeebaBackup8 = ComponentHelper::isInstalled('com_akeeba');

		if (!$hasAkeebaBackup8)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_MIGRATE_NOAB8'));

			return 1;
		}

		/** @var UpgradeModel $model */
		$model   = $this->getMVCFactory()->createModel('Upgrade', 'Administrator');
		$model->init();
		$results = $model->runCustomHandlerEvent('onMigrateSettings');
		$success = in_array(true, $results, true);

		if (!$success)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_FAIL'));

			return 1;
		}

		$this->ioStyle->success(Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_SUCCESS'));

		return 0;
	}

	protected function configure(): void
	{
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_MIGRATE_COMMAND_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MIGRATE_HELP'));
	}

}