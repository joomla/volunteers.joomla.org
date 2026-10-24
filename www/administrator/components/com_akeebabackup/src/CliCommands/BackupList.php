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
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:backup:list
 *
 * Lists backup records known to Akeeba Backup
 *
 * @since   7.5.0
 */
class BackupList extends AbstractCommand
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
	protected static $defaultName = 'akeeba:backup:list';

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

		$from    = (int) ($this->cliInput->getOption('from') ?? 0);
		$limit   = (int) ($this->cliInput->getOption('limit') ?? 0);
		$format  = (string) ($this->cliInput->getOption('format') ?? 'table');
		$filters = $this->getFilters();
		$order   = $this->getOrdering();

		if ($format === 'table')
		{
			$this->ioStyle->title(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_HEAD'));
		}

		/** @var StatisticsModel $model */
		$model = $this->getMVCFactory()->createModel('Statistics', 'Administrator');

		$model->setState('list.start', $from);
		$model->setState('list.limit', $limit);
		$model->setStateSetFlag();

		$output = $model->getStatisticsListWithMeta(false, $filters, $order);

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
		$this->addOption('from', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_FROM'), 0);
		$this->addOption('limit', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_LIMIT'), 50);
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_FORMAT'), 'table');
		$this->addOption('description', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_DESCRIPTION'));
		$this->addOption('after', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_AFTER'));
		$this->addOption('before', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_BEFORE'));
		$this->addOption('origin', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_ORIGIN'));
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_PROFILE'));
		$this->addOption('sort-by', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_SORT_BY'), 'id');
		$this->addOption('sort-order', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_OPT_SORT_ORDER'), 'desc');
		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_BACKUP_LIST_HELP'));
	}

	private function getFilters(): ?array
	{
		$filters = [];

		$description = $this->cliInput->getOption('description') ?? '';

		if ($description)
		{
			$filters[] = [
				'field'   => 'description',
				'operand' => 'LIKE',
				'value'   => $description,
			];
		}

		$after  = $this->cliInput->getOption('after') ?? '';
		$before = $this->cliInput->getOption('before') ?? '';

		if (!empty($after) && !empty($before))
		{
			$filters[] = [
				'field'   => 'backupstart',
				'operand' => 'BETWEEN',
				'value'   => $after,
				'value2'  => $before,
			];
		}
		elseif (!empty($after))
		{
			$filters[] = [
				'field'   => 'backupstart',
				'operand' => '>=',
				'value'   => $after,
			];
		}
		elseif (!empty($before))
		{
			$filters[] = [
				'field'   => 'backupstart',
				'operand' => '<=',
				'value'   => $before,
			];
		}

		$origin = $this->cliInput->getOption('origin') ?? '';

		if (!empty($origin))
		{
			$filters[] = [
				'field'   => 'origin',
				'operand' => '=',
				'value'   => $origin,
			];
		}

		$profile = (int) ($this->cliInput->getOption('profile') ?? 0);

		if ($profile > 0)
		{
			$filters[] = [
				'field'   => 'profile_id',
				'operand' => '=',
				'value'   => $profile,
			];
		}

		return !empty($filters) ? $filters : null;
	}

	private function getOrdering(): array
	{
		$order = strtolower($this->cliInput->getOption('sort-order') ?? 'desc');
		$order = in_array($order, ['asc', 'desc']) ?: 'desc';

		return [
			'by'    => $this->cliInput->getOption('sort-by') ?? 'id',
			'order' => $order,
		];
	}
}
