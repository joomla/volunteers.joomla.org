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
use Akeeba\Engine\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:filter:delete
 *
 * Delete a filter value known to Akeeba Backup.
 *
 * @since   7.5.0
 */
class FilterDelete extends AbstractCommand
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
	protected static $defaultName = 'akeeba:filter:delete';

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

		$filterType = (string) ($this->cliInput->getOption('filterType') ?? 'files');
		$target     = (in_array($filterType, [
			'tables', 'tabledata', 'regextables', 'regextabledata', 'multidb',
		])) ? 'db' : 'fs';
		$root       = (string) ($this->cliInput->getOption('root') ?? (($target == 'fs') ? '[SITEROOT]' : '[SITEDB]'));

		if (!in_array($root, $this->getRoots($target)))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_DELETE_ERR_UNKNOWN_ROOT', $target, $root));

			return 1;
		}

		$filter = (string) $this->cliInput->getArgument('filter') ?? '';

		$this->ioStyle->title(Text::sprintf(
			'COM_AKEEBABACKUP_CLI_FILTER_DELETE_HEAD',
			$target === 'db' ? Text::_('COM_AKEEBABACKUP_CLI_FILTER_TYPE_DATABASE') : Text::_('COM_AKEEBABACKUP_CLI_FILTER_TYPE_FILESYSTEM'),
			$filter,
			$filterType,
			$profileId
		));

		// Delete the filter
		$filterObject = Factory::getFilterObject($filterType);

		if ((stripos($filterType, 'regex') !== false) && !$this->isPro())
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_DELETE_ONLY_PRO', $filterType));

			return 2;
		}

		switch ($filterType)
		{
			case 'extradirs':
			case 'multidb':
				if (!$this->isPro())
				{
					$this->ioStyle->error(Text::sprintf("COM_AKEEBABACKUP_CLI_FILTER_DELETE_ONLY_PRO", $filterType));

					return 2;
				}

				$success = $filterObject->remove($filter);
				break;

			default:
				$success = $filterObject->remove($root, $filter);
				break;
		}

		if (!$success)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_DELETE_ERR_FAILED', $filter, $filterType));

			return 3;
		}

		Factory::getFilters()->save();

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_DELETE_LBL_DELETED', $filter, $filterType));

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
		$this->addArgument('filter', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_FILTER_DELETE_OPT_FILTER'));
		$this->addOption('root', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_DELETE_OPT_ROOT'), '');
		$this->addOption('filterType', null, InputOption::VALUE_REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_FILTER_DELETE_OPT_FILTERTYPE'), 'files');
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_DELETE_OPT_PROFILE'), 1);

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_FILTER_DELETE_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_FILTER_DELETE_HELP'));
	}
}
