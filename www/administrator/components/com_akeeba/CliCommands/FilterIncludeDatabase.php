<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Backup\Admin\Model\MultipleDatabases;
use FOF40\Container\Container;
use Joomla\Console\Command\AbstractCommand;
use Akeeba\Backup\Admin\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Backup\Admin\CliCommands\MixIt\ConfigureIO;
use Akeeba\Backup\Admin\CliCommands\MixIt\FilterRoots;
use Akeeba\Backup\Admin\CliCommands\MixIt\IsPro;
use Akeeba\Backup\Admin\CliCommands\MixIt\PrintFormattedArray;
use Joomla\Plugin\Console\AkeebaBackup\Helper\UUID4;
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
	use ConfigureIO, ArgumentUtilities, PrintFormattedArray, IsPro, FilterRoots;

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

		if (!$this->isPro())
		{
			$this->ioStyle->error('This feature requires Akeeba Backup Professional');

			return 1;
		}

		$profileId = (int) ($this->cliInput->getOption('profile') ?? 1);

		define('AKEEBA_PROFILE', $profileId);

		// Initialization
		$uuidObject = new UUID4(true);
		$uuid       = $uuidObject->get('-');
		$check      = (bool) $this->cliInput->getOption('check') ?? false;

		$data = [
			'driver'   => (string) $this->cliInput->getOption('dbdriver') ?? 'mysqli',
			'host'     => (string) $this->cliInput->getOption('dbhost') ?? 'localhost',
			'port'     => (int) $this->cliInput->getOption('port') ?? 0,
			'user'     => (string) $this->cliInput->getOption('dbusername') ?? '',
			'password' => (string) $this->cliInput->getOption('dbpassword') ?? '',
			'database' => (string) $this->cliInput->getOption('dbname') ?? '',
			'prefix'   => (string) $this->cliInput->getOption('dbprefix') ?? '',
		];

		$data['port'] = ($data['port'] === 0) ? null : $data['port'];

		// Does the database definition already exist?
		$container = Container::getInstance('com_akeeba');
		/** @var MultipleDatabases $model */
		$model = $container->factory->model('MultipleDatabases')->tmpInstance();

		if ($model->filterExists($data))
		{
			$this->ioStyle->error(sprintf("The database '%s' is already included. Delete the old inclusion filter before trying to add the database again.", $data['database']));

			return 2;
		}

		// Can I connect to the database?
		$checkResults = $model->test($data);

		if ($check && !$checkResults['status'])
		{
			$this->ioStyle->error(sprintf("Could not connect to the database '%s'. Server reported '%s'. Use the --no-check option to continue anyway but be advised that your backup will most likely result in an error.", $data['database'], $checkResults['message']));

			return 3;
		}

		// Add the filter
		if (!$model->setFilter($uuid, $data))
		{
			$this->ioStyle->error(sprintf("Could not include database '%s'.", $data['database']));

			return 4;
		}

		$this->ioStyle->success(sprintf("Added database '%s'.", $data['database']));

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
		$help = "<info>%command.name%</info> will add an additional database to be backed up by Akeeba Backup.
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, 'The backup profile to use. Default: 1.', 1);
		$this->addOption('dbdriver', null, InputOption::VALUE_OPTIONAL, 'The database driver to use: mysqli, mysql, pdomysql', 'mysqli');
		$this->addOption('dbport', null, InputOption::VALUE_OPTIONAL, 'The database server port. Skip to use the driver\'s default.', null);
		$this->addOption('dbusername', null, InputOption::VALUE_REQUIRED, 'The database connection username.');
		$this->addOption('dbpassword', null, InputOption::VALUE_REQUIRED, 'The database connection password.');
		$this->addOption('dbname', null, InputOption::VALUE_REQUIRED, 'The database name.');
		$this->addOption('dbprefix', null, InputOption::VALUE_OPTIONAL, 'The common prefix of the database table names, allows you to change it on restoration.', null);
		$this->addOption('check', null, InputOption::VALUE_NONE, 'Check the database connection before adding the filter.');

		$this->setDescription('Adds an additional database to be backed up by Akeeba Backup.');
		$this->setHelp($help);
	}
}
