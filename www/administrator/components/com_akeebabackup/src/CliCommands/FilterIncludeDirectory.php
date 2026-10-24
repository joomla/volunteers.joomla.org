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
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\FilterRoots;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\IsPro;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Akeeba\Component\AkeebaBackup\Administrator\Model\IncludefoldersModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\RandomValue;
use Akeeba\Plugin\Console\AkeebaBackup\Helper\UUID4;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:filter:include-directory
 *
 * Add an additional off-site directory to be backed up by Akeeba Backup.
 *
 * @since   7.5.0
 */
class FilterIncludeDirectory extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use IsPro;
	use FilterRoots;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:filter:include-directory';

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

		if (!$this->isPro())
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_ERR_NEED_PRO'));

			return 1;
		}

		$profileId = (int) ($this->cliInput->getOption('profile') ?? 1);

		define('AKEEBA_PROFILE', $profileId);

		// Initialization
		$uuidObject = new UUID4(true);
		$virtual    = (string) $this->cliInput->getOption('virtual') ?? '';
		$uuid       = $uuidObject->get('-');
		$directory  = (string) $this->cliInput->getArgument('directory') ?? '';

		// Does the database definition already exist?
		/** @var IncludefoldersModel $model */
		$model      = $this->getMVCFactory()->createModel('Includefolders', 'Administrator');
		$allFilters = $model->get_directories();

		foreach ($allFilters as $root => $filterData)
		{
			if ($filterData[0] == $directory)
			{
				$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_ERR_EXISTS', $directory, $root));

				return 2;
			}
		}

		// Create a new inclusion filter
		if (empty($virtual))
		{
			$randomValue  = new RandomValue();
			$randomPrefix = $randomValue->generateString(8);
			$virtual      = $randomPrefix . '-' . basename($directory);
		}

		$data = [
			0 => $directory,
			1 => $virtual,
		];

		$filterObject = Factory::getFilterObject('extradirs');
		$success      = $filterObject->set($uuid, $data);

		$filters = Factory::getFilters();

		if (!$success)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_ERR_FAILED', $directory));

			return 3;
		}

		// Save to the database
		$filters->save();

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_LBL_SUCCESS', $directory));

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
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_OPT_PROFILE'), 1);
		$this->addArgument('directory', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_OPT_DIRECTORY'));
		$this->addOption('virtual', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_OPT_VIRTUAL'), null);

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_FILTER_INCLUDEFOLDER_HELP'));
	}
}
