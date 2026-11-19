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
use Akeeba\Component\AkeebaBackup\Administrator\Model\MultipledatabasesModel;
use Akeeba\Plugin\Console\AkeebaBackup\Helper\UUID4;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:filter:include-database
 *
 * Add an additional database to be backed up by Akeeba Backup.
 *
 * @since   7.5.0
 */
class FilterIncludeDatabase extends AbstractCommand
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
	protected static $defaultName = 'akeeba:filter:include-database';

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
		$uuid       = $uuidObject->get('-');
		$check      = (bool) $this->cliInput->getOption('check') ?? false;

		$host = trim((string) ($this->cliInput->getOption('dbhost') ?? ''));

		// The keys must match what the Multiple Databases page (MultipleDatabases.js) stores, and the engine reads.
		$data = [
			'driver'   => (string) ($this->cliInput->getOption('dbdriver') ?? 'mysqli'),
			'host'     => $host === '' ? 'localhost' : $host,
			'port'     => (int) ($this->cliInput->getOption('dbport') ?? 0),
			'username' => (string) ($this->cliInput->getOption('dbusername') ?? ''),
			'password' => (string) ($this->cliInput->getOption('dbpassword') ?? ''),
			'database' => (string) ($this->cliInput->getOption('dbname') ?? ''),
			'prefix'   => (string) ($this->cliInput->getOption('dbprefix') ?? ''),
		];

		$data['port']     = ($data['port'] === 0) ? null : $data['port'];
		$data['dumpFile'] = substr($uuid, 0, 9) . $data['database'] . '.sql';

		// Does the database definition already exist?
		/** @var MultipledatabasesModel $model */
		$model = $this->getMVCFactory()->createModel('Multipledatabases', 'Administrator');

		if ($model->filterExists($data))
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_ERR_ALREADY_EXISTS', $data['database']));

			return 2;
		}

		// Can I connect to the database? The connection test reads the username from the `user` key.
		if ($check)
		{
			$checkResults = $model->test(array_merge($data, ['user' => $data['username']]));
		}

		if ($check && !$checkResults['status'])
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_ERR_CANNOT_CONNECT', $data['database'], $checkResults['message']));

			return 3;
		}

		// Add the filter
		$setFilterResult = $model->setFilter($uuid, $data);

		if (!$setFilterResult['success'])
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_ERR_FAILED', $data['database']));

			return 4;
		}

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_LBL_ADDED', $data['database']));

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
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_PROFILE'), 1);
		$this->addOption('dbdriver', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBDRIVER'), 'mysqli');
		$this->addOption('dbhost', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBHOST'), 'localhost');
		$this->addOption('dbport', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBPORT'), null);
		$this->addOption('dbusername', null, InputOption::VALUE_REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBUSERNAME'));
		$this->addOption('dbpassword', null, InputOption::VALUE_REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBPASSWORD'));
		$this->addOption('dbname', null, InputOption::VALUE_REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBNAME'));
		$this->addOption('dbprefix', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_DBPREFIX'), null);
		$this->addOption('check', null, InputOption::VALUE_NONE, Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_OPT_CHECK'));

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_FILTER_MULTIDB_HELP'));
	}
}
