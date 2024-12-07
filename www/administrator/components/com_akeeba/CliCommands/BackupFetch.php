<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Backup\Admin\Model\RemoteFiles;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use FOF40\Container\Container;
use Joomla\Console\Command\AbstractCommand;
use Akeeba\Backup\Admin\CliCommands\MixIt\ArgumentUtilities;
use Akeeba\Backup\Admin\CliCommands\MixIt\ConfigureIO;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:fetch
 *
 * Download a backup from the remote storage back to the server
 *
 * @since   7.5.0
 */
class BackupFetch extends AbstractCommand
{
	use ConfigureIO, ArgumentUtilities;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:fetch';

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

		$id = (int) $this->cliInput->getArgument('id') ?? 0;

		$this->ioStyle->title(sprintf('Retrieving the remotely stored files for Akeeba Backup record #%d', $id));

		if ($id <= 0)
		{
			$this->ioStyle->error('Invalid backup record');

			return 1;
		}

		$record = Platform::getInstance()->get_statistics($id);

		if (!is_array($record))
		{
			$this->ioStyle->error('Invalid backup record');

			return 1;
		}

		$container = Container::getInstance('com_akeeba', [], 'admin');

		// Set the correct profile ID
		$profileId = $record['profile_id'];
		$container->platform->setSessionVar('profile', $profileId, 'akeeba');
		Platform::getInstance()->load_configuration($profileId);

		/** @var RemoteFiles $model */
		$model     = $container->factory->model('RemoteFiles')->tmpInstance();
		$part      = 0;
		$frag      = 0;
		$totalSize = 0;
		$doneSize  = 0;

		$configuration = Factory::getConfiguration();
		$configuration->set('akeeba.tuning.max_exec_time', 1);
		$configuration->set('akeeba.tuning.run_time_bias', 10);

		$this->ioStyle->section(sprintf('Downloading backup archive for backup #%d', $id));
		$progress = $this->ioStyle->createProgressBar(1);
		$progress->display();

		while (true)
		{
			if ($totalSize > 0)
			{
				$progress->setMaxSteps($totalSize);
				$progress->setProgress($doneSize);

				$progress->setMessage(sprintf('Part file: %d, file fragment: %d', $part, $frag));
			}

			try
			{
				// Try downloading
				$result = $model->downloadToServer($id, $part, $frag);

				// Get the modified model state
				$id   = $model->getState('id');
				$part = $model->getState('part');
				$frag = $model->getState('frag');

				// Get session variables
				$totalSize = $container->platform->getSessionVar('dl_totalsize', 0, 'akeeba');
				$doneSize  = $container->platform->getSessionVar('dl_donesize', 0, 'akeeba');

				// Are we done yet?
				if (($part >= 0) && ($result === true))
				{
					$totalSize = max($totalSize, $doneSize);
					$progress->setMaxSteps($totalSize);
					$progress->setProgress($doneSize);
					$progress->finish();

					$this->ioStyle->newLine(2);

					$this->ioStyle->success(sprintf("Retrieving files of backup record '%s' has finished.", $id));

					return 0;
				}
			}
			catch (\Exception $e)
			{
				$this->ioStyle->newLine(2);

				$errorMessage = $e->getMessage();
				$this->ioStyle->error(["Retrieving files of backup record '$id' failed.", $errorMessage]);

				return 2;
			}
		}
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
		$help = "<info>%command.name%</info> will download the backup archives of a backup known to Akeeba backup from the remote storage back to the server.
		\nUsage: <info>php %command.full_name%</info>";

		$this->addArgument('id', InputArgument::REQUIRED, 'The id of the backup record to retrieve');
		$this->setDescription('Download a backup from the remote storage back to the server');
		$this->setHelp($help);
	}
}
