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
use Akeeba\Component\AkeebaBackup\Administrator\Model\DatabasefiltersModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\FilefiltersModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\IncludefoldersModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\MultipledatabasesModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\RegexdatabasefiltersModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\RegexfilefiltersModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:filter:list
 *
 * Get the filter values known to Akeeba Backup.
 *
 * @since   7.5.0
 */
class FilterList extends AbstractCommand
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
	protected static $defaultName = 'akeeba:filter:list';

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

		$root   = (string) ($this->cliInput->getOption('root') ?? '');
		$target = (string) ($this->cliInput->getOption('target') ?? 'fs');
		$type   = (string) ($this->cliInput->getOption('type') ?? 'exclude');
		$format = (string) ($this->cliInput->getOption('format') ?? 'table');

		if (!in_array($target, ['fs', 'db']))
		{
			$target = 'fs';
		}

		if (!in_array($type, ['include', 'exclude', 'regex']))
		{
			$type = 'exclude';
		}

		if (!$this->isPro())
		{
			$type = 'exclude';
		}

		$roots = $this->getRoots($target);

		if (empty($root))
		{
			$root = ($target == 'fs') ? '[SITEROOT]' : '[SITEDB]';
		}

		$output = [];

		if (!in_array($root, $roots))
		{
			$this->ioStyle->error(Text::sprintf(
				'COM_AKEEBABACKUP_CLI_FILTER_DELETE_ERR_UNKNOWN_ROOT',
				$target === 'db' ? Text::_('COM_AKEEBABACKUP_CLI_FILTER_TYPE_DATABASE') : Text::_('COM_AKEEBABACKUP_CLI_FILTER_TYPE_FILESYSTEM'),
				$root
			));

			return 1;
		}


		if ($format === 'table')
		{
			$this->ioStyle->title(Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_TITLE'));
		}

		switch ("$target.$type")
		{
			case "fs.exclude":
				/** @var FilefiltersModel $model */
				$model      = $this->getMVCFactory()->createModel('Filefilters', 'Administrator');
				$allFilters = $model->getFilters($root);

				foreach ($allFilters as $item)
				{
					$output[] = [
						'filter' => $item['node'],
						'type'   => $item['type'],
					];
				}

				break;

			case "fs.regex":
				/** @var RegexfilefiltersModel $model */
				$model      = $this->getMVCFactory()->createModel('Regexfilefilters', 'Administrator');
				$allFilters = $model->get_regex_filters($root);

				foreach ($allFilters as $item)
				{
					$output[] = [
						'filter' => $item['item'],
						'type'   => $item['type'],
					];
				}

				break;

			case "fs.include":
				/** @var IncludefoldersModel $model */
				$model      = $this->getMVCFactory()->createModel('Includefolders', 'Administrator');
				$allFilters = $model->get_directories();

				foreach ($allFilters as $uuid => $item)
				{
					$output[] = [
						'filter'               => $uuid,
						'type'                 => 'extradirs',
						'filesystem_directory' => $item[0],
						'virtual_directory'    => $item[1],
					];
				}

				break;

			case "db.exclude":
				/** @var DatabasefiltersModel $model */
				$model      = $this->getMVCFactory()->createModel('Databasefilters', 'Administrator');
				$allFilters = $model->getFilters($root);

				foreach ($allFilters as $item)
				{
					$output[] = [
						'filter' => $item['node'],
						'type'   => $item['type'],
					];
				}

				break;

			case "db.regex":
				/** @var RegexdatabasefiltersModel $model */
				$model      = $this->getMVCFactory()->createModel('Regexdatabasefilters', 'Administrator');
				$allFilters = $model->get_regex_filters($root);

				foreach ($allFilters as $item)
				{
					$output[] = [
						'filter' => $item['item'],
						'type'   => $item['type'],
					];
				}

				break;

			case "db.include":
				/** @var MultipledatabasesModel $model */
				$model      = $this->getMVCFactory()->createModel('Multipledatabases', 'Administrator');
				$allFilters = $model->get_databases();

				foreach ($allFilters as $uuid => $item)
				{
					$output[] = [
						'filter'   => $uuid,
						'type'     => 'multidb',
						'host'     => $item['host'],
						'driver'   => $item['driver'],
						'port'     => $item['port'],
						'username' => $item['username'],
						'password' => $item['password'],
						'database' => $item['database'],
						'prefix'   => $item['prefix'],
						'dumpFile' => $item['dumpFile'],
					];
				}

				break;
		}

		return $this->printFormattedAndReturn($output, $format);
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
		$this->addOption('root', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_OPT_ROOT'), '');
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_OPT_PROFILE'), 1);
		$this->addOption('target', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_OPT_TARGET'), 'fs');
		$this->addOption('type', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_OPT_TYPE'), 'exclude');
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_OPT_FORMAT'), 'table');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_FILTER_LIST_HELP'));
	}
}
