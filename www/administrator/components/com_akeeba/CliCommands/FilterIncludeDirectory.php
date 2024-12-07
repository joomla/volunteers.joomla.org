<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Backup\Admin\Model\IncludeFolders;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\RandomValue;
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
 * akeeba:filter:include-directory
 *
 * Add an additional off-site directory to be backed up by Akeeba Backup.
 *
 * @since   7.5.0
 */
class FilterIncludeDirectory extends AbstractCommand
{
	use ConfigureIO, ArgumentUtilities, PrintFormattedArray, IsPro, FilterRoots;

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

		if (!$this->isPro())
		{
			$this->ioStyle->error('This feature requires Akeeba Backup Professional');

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
		$container = Container::getInstance('com_akeeba');
		/** @var IncludeFolders $model */
		$model      = $container->factory->model('IncludeFolders')->tmpInstance();
		$allFilters = $model->get_directories();

		foreach ($allFilters as $root => $filterData)
		{
			if ($filterData[0] == $directory)
			{
				$this->ioStyle->error(sprintf("The directory '%s' is already included with root '%s'. Delete the old inclusion filter before trying to add the directory again.", $directory, $root));

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
			$this->ioStyle->error(sprintf("Could not add directory '%s'.", $directory));

			return 3;
		}

		// Save to the database
		$filters->save();

		$this->ioStyle->success("Added directory '$directory'.");

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
		$help = "<info>%command.name%</info> will add an additional off-site directory to be backed up by Akeeba Backup.
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, 'The backup profile to use. Default: 1.', 1);
		$this->addArgument('directory', InputOption::VALUE_REQUIRED, 'Full path to theoff-site directory to add to the backup.');
		$this->addOption('virtual', null, InputOption::VALUE_OPTIONAL, 'The subfolder inside the backup archive where these files will be stored. This is a subfolder of the "virtual directory" whose name is set in the Configuration page. Skip to determine automatically.', null);

		$this->setDescription('Add an additional off-site directory to be backed up by Akeeba Backup.');
		$this->setHelp($help);
	}
}
