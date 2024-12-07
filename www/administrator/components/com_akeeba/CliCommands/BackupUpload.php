<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\CliCommands;

defined('_JEXEC') || die;

use Akeeba\Backup\Admin\Model\Upload;
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
 * akeeba:backup:upload
 *
 * Retry uploading a backup to the remote storage
 *
 * @since   7.5.0
 */
class BackupUpload extends AbstractCommand
{
	use ConfigureIO, ArgumentUtilities;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:upload';

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

		$this->ioStyle->title(sprintf('Re-uploading Akeeba Backup record #%d', $id));

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

		/** @var Upload $model */
		$model = $container->factory->model('Upload')->tmpInstance();
		$part  = 0;
		$frag  = 0;

		$configuration = Factory::getConfiguration();
		$configuration->set('akeeba.tuning.max_exec_time', 1);
		$configuration->set('akeeba.tuning.run_time_bias', 10);

		while (true)
		{
			$this->ioStyle->writeln(sprintf("Trying to re-upload backup record '%s', part file #%s, fragment #%s. This may take a while.", $id, $part, $frag));

			try
			{// Try uploading
				$result = $model->upload($id, $part, $frag);// Get the modified model state
				$id     = $model->getState('id');
				$part   = $model->getState('part');
				$frag   = $model->getState('frag');
				if (($part >= 0) && ($result === true))
				{
					$this->ioStyle->newLine(2);

					$this->ioStyle->success(sprintf("Re-upload of backup record '%s' is complete.", $id));

					return 0;
				}
			}
			catch (\Exception $e)
			{
				$this->ioStyle->newLine(2);

				$errorMessage = $e->getMessage();
				$this->ioStyle->error(["Re-upload of backup record '$id' failed.", $errorMessage]);

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
		$help = "<info>%command.name%</info> will retry uploading a backup known to Akeeba Backup to the remote storage.
		\nUsage: <info>php %command.full_name%</info>";

		$this->addArgument('id', InputArgument::REQUIRED, 'The id of the backup record to upload');
		$this->setDescription('Retry uploading a backup to the remote storage');
		$this->setHelp($help);
	}
}
