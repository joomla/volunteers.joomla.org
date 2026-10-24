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
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\JsonGuiDataParser;
use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\PrintFormattedArray;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * akeeba:option:list
 *
 * Lists the configuration options for an Akeeba Backup profile, including their titles
 *
 * @since   7.5.0
 */
class OptionsList extends AbstractCommand
{
	use ConfigureIO;
	use ArgumentUtilities;
	use PrintFormattedArray;
	use JsonGuiDataParser;
	use MVCFactoryAwareTrait;
	use InitialiseEngine;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  7.5.0
	 */
	protected static $defaultName = 'akeeba:option:list';

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

		$format = (string) $this->cliInput->getOption('format') ?? 'table';

		/** @var ProfileModel $model */
		$model   = $this->getMVCFactory()->createModel('Profile', 'Administrator');
		$table   = $model->getTable();
		$didLoad = $table->load($profileId);

		if (!$didLoad)
		{
			$this->ioStyle->error(Text::sprintf('COM_AKEEBABACKUP_CLI_OPTIONS_GET_ERR_INVALID_PROFILE', $profileId));

			return 1;
		}

		unset($table);
		unset($model);

		// Get the profile's configuration
		Platform::getInstance()->load_configuration($profileId);
		$config  = Factory::getConfiguration();
		$rawJson = $config->exportAsJSON();

		unset($config);

		// Get the key information from the GUI data
		$info = $this->parseJsonGuiData();

		// Convert the INI data we got into an array we can print
		$rawValues = json_decode($rawJson, true);

		unset($rawJson);

		$output = [];

		$rawValues = $this->flattenOptions($rawValues);

		foreach ($rawValues as $key => $v)
		{
			$output[$key] = array_merge([
				'key'          => $key,
				'value'        => $v,
				'title'        => '',
				'description'  => '',
				'type'         => '',
				'default'      => '',
				'section'      => '',
				'options'      => [],
				'optionTitles' => [],
				'limits'       => [],
			], $this->getOptionInfo($key, $info));
		}

		// Filter the returned options
		$filter = (string) $this->cliInput->getOption('filter') ?? '';

		$output = array_filter($output, function ($item) use ($filter) {
			if (!empty($filter) && strpos($item['key'], $filter) === false)
			{
				return false;
			}

			return $item['type'] != 'hidden';
		});

		// Sort the results
		$sort  = (string) $this->cliInput->getOption('sort-by') ?? 'none';
		$order = (string) $this->cliInput->getOption('sort-order') ?? 'asc';

		if ($sort != 'none')
		{
			usort($output, function ($a, $b) use ($sort, $order) {
				if ($a[$sort] == $b[$sort])
				{
					return 0;
				}

				$signChange = ($order == 'asc') ? 1 : -1;
				$isGreater  = $a[$sort] > $b[$sort] ? 1 : -1;

				return $signChange * $isGreater;
			});
		}

		// Output the list
		if (empty($output))
		{
			$this->ioStyle->error(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_ERR_NO_SUCH_OPTION'));

			return 2;
		}

		if ($format === 'table')
		{
			$output = array_map(function (array $optionDef) {
				return array_map(function ($value) {
					return is_array($value) ? implode(', ', $value) : $value;
				}, $optionDef);
			}, $output);
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
		$this->addOption('profile', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_OPT_PROFILE'), 1);
		$this->addOption('filter', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_OPT_FILTER'), '');
		$this->addOption('sort-by', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_OPT_SORT_BY'), 'none');
		$this->addOption('sort-order', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_OPT_SORT_ORDER'), 'desc');
		$this->addOption('format', null, InputOption::VALUE_OPTIONAL, Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_OPT_FORMAT'), 'table');

		$this->setDescription(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_DESC'));
		$this->setHelp(Text::_('COM_AKEEBABACKUP_CLI_OPTIONS_LIST_HELP'));
	}
}
