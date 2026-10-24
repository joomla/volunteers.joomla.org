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
use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:modify
 *
 * Modifies a backup record known to Akeeba Backup
 *
 * @since   7.5.0
 */
class BackupModify extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:backup:modify';

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

		$id          = (int) $this->cliInput->getArgument('id') ?? 0;
		$description = $this->cliInput->getOption('description');
		$comment     = $this->cliInput->getOption('comment');

		$this->ioStyle->title(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_HEAD', $id));

		if ($id <= 0)
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));

			return 1;
		}

		if (is_null($description) && is_null($comment))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_ERR_DESC_AND_COMMENT_REQUIRED'));

			return 2;
		}

		$record = Platform::getInstance()->get_statistics($id);

		if (empty($record))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));

			return 1;
		}

		if (!is_null($description))
		{
			$record['description'] = (string) $description;
		}

		if (!is_null($comment))
		{
			$record['comment'] = (string) $comment;
		}

		$result = Platform::getInstance()->set_or_update_statistics($id, $record);

		if ($result === false)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_ERR_CANNOT_MODIFY', $id));

			return 3;
		}

		$this->ioStyle->success(Text::sprintf('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_LBL_MODIFIED', $id));

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
		$this->addArgument('id', InputArgument::REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_OPT_ID'));
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_OPT_DESCRIPTION'));
		$this->addOption('comment', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_OPT_COMMENT'));
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_MODIFY_HELP'));
	}
}
