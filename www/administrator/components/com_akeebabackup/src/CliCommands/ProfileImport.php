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
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Component\AkeebaBackup\Administrator\Table\ProfileTable;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:profile:import
 *
 * Imports an Akeeba Backup profile from a JSON string.
 *
 * @since   7.5.0
 */
class ProfileImport extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:profile:import';

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

        $filename = $this->cliInput->getArgument('fileOrJSON');
		$json     = $this->getJSON($filename);

		try
		{
			$decoded = @json_decode($json, true);
		}
		catch (Exception $e)
		{
			$decoded = '';
		}

		if (empty($decoded))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_ERR_INVALID_JSON'));

			return 1;
		}

		$profileOption = $this->cliInput->getOption('profile');
		$profileId     = is_null($profileOption) ? null : (int) $profileOption;
		$force         = (bool) $this->cliInput->getOption('force');

		if (!is_null($profileId) && $profileId <= 0)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_ERR_INVALID_PROFILE', $profileOption));

			return 3;
		}

		/**
		 * Never trust the ID in the imported data. The target profile ID is decided solely by the --profile option;
		 * without it we let the database assign the next available ID.
		 */
		if (isset($decoded['id']))
		{
			unset($decoded['id']);
		}

		/** @var ProfileModel $model */
		$model = $this->getMVCFactory()->createModel('Profile', 'Administrator');
		/** @var ProfileTable $table */
		$table = $model->getTable();

		// Does the requested profile already exist? Note that profile #1, the default profile, always does.
		$exists = !is_null($profileId) && $table->load($profileId);

		if ($exists && !$force)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_ERR_EXISTS', $profileId));

			return 4;
		}

		try
		{
			if (is_null($profileId))
			{
				$result = $table->save($decoded);
			}
			elseif ($exists)
			{
				/**
				 * The record is already loaded, therefore any key missing from the imported data — e.g. filters, when
				 * the export was made without the --filters option — retains its current value.
				 */
				$decoded['id'] = $profileId;
				$result        = $table->save($decoded);
			}
			else
			{
				$result = $this->insertWithId($table, $decoded, $profileId);
			}

			$error = null;
		}
		catch (Exception $e)
		{
			$result = false;
			$error  = $e->getMessage();
		}

		if ($result === false && $error === null)
		{
			/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
			/** @noinspection PhpDeprecationInspection */
			$error = $table->getError();
		}

		if (!$result)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_ERR_GENERIC', $error));

			return 2;
		}

		$id     = $table->getId();
		$format = (string) $this->cliInput->getOption('format') ?? 'text';

		if ($format == 'json')
		{
			echo json_encode($id);

			return 0;
		}

		$this->ioStyle->success(
			Text::sprintf(
				$exists
					? 'COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_LBL_SUCCESS_OVERWRITE'
					: 'COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_LBL_SUCCESS',
				$id
			)
		);

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
		$this->addArgument('fileOrJSON', InputArgument::OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_OPT_FILEORJSON'));
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_OPT_FORMAT'), 'text');
		$this->addOption('profile', null, InputOption::VALUE_REQUIRED, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_OPT_PROFILE'));
		$this->addOption('force', null, InputOption::VALUE_NONE, Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_OPT_FORCE'));

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_PROFILE_IMPORT_HELP'));
	}

	/**
	 * Get the JSON input
	 *
	 * @param   string|null  $filename  The filename to read from, raw JSON data or an empty string
	 *
	 * @return  string  The JSON data
	 *
	 * @since   7.5.0
	 */
	private function getJSON(?string $filename): string
	{
		// No filename or JSON string passed to script; use STDIN
		if (empty($filename))
		{
			$json = '';

			while (!feof(STDIN))
			{
				$json .= fgets(STDIN) . "\n";
			}

			return rtrim($json);
		}

		// An existing file path was passed. Return the contents of the file.
		if (@file_exists($filename))
		{
			$ret = @file_get_contents($filename);

			if ($ret === false)
			{
				return '';
			}

            return $ret;
		}

		// Otherwise assume raw JSON was passed back to us.
		return $filename;
	}

	/**
	 * Create a brand-new backup profile using a specific, currently unused, profile ID.
	 *
	 * This cannot go through Table::save(). Table::store() issues an UPDATE whenever the primary key is non-empty, so
	 * setting the ID on an unsaved record would update zero rows while still reporting success. We therefore bind and
	 * validate through the Table object, then INSERT the record ourselves.
	 *
	 * @param   ProfileTable  $table      The profile table object, reset to a pristine state
	 * @param   array         $data       The imported profile data
	 * @param   int           $profileId  The profile ID to create the record with
	 *
	 * @return  bool  True on success
	 *
	 * @since   10.4.0
	 */
	private function insertWithId(ProfileTable $table, array $data, int $profileId): bool
	{
		$table->reset();

		if (!$table->bind($data) || !$table->check())
		{
			return false;
		}

		$table->id = $profileId;

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// insertObject() takes its second argument by reference; it cannot be given an inline cast.
		$record = (object) $table->getProperties();

		$db->insertObject($table->getTableName(), $record);

		/**
		 * PostgreSQL declares the ID column as GENERATED BY DEFAULT AS IDENTITY. Inserting an explicit ID does not
		 * advance the identity sequence, so a subsequent auto-ID insert would collide with the row we just wrote.
		 */
		if ($db->getServerType() === 'postgresql')
		{
			$tableName = $db->replacePrefix($table->getTableName());

			$db->setQuery(
				'SELECT setval(pg_get_serial_sequence(' . $db->quote($tableName) . ', ' . $db->quote('id') . '), '
				. 'GREATEST((SELECT MAX(id) FROM ' . $db->quoteName($tableName) . '), 1))'
			)->execute();
		}

		return true;
	}

}
