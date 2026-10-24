<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Manage;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewBackupStartTimeTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewListLimitFixTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTableUITrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfilesModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\TransferModel;
use Akeeba\Engine\Factory as AkeebaFactory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text as Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Uri\Uri as JUri;
use Joomla\Registry\Registry;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewBackupStartTimeTrait;
	use ViewTableUITrait;
	use ViewListLimitFixTrait;
	use ViewToolbarTrait;

	/**
	 * The active search filters
	 *
	 * @since  1.6
	 * @var    array
	 */
	public $activeFilters = [];

	/**
	 * The search tools form
	 *
	 * @since  1.6
	 * @var    Form
	 */
	public $filterForm;

	/**
	 * List of frozen options for JHtmlSelect
	 *
	 * @var  array
	 */
	public $frozenList = [];

	/**
	 * List of records to display
	 *
	 * @var  array
	 */
	public $items = [];

	/**
	 * Order direction, ASC/DESC
	 *
	 * @var  string
	 */
	public $order_Dir = 'DESC';

	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination = null;

	/**
	 * Cache the user permissions
	 *
	 * @since 5.3.0
	 * @var   array
	 *
	 */
	public $permissions = [];

	/**
	 * List of Profiles objects
	 *
	 * @var  array
	 */
	public $profiles = [];

	/**
	 * List of profiles for JHtmlSelect
	 *
	 * @var  array
	 */
	public $profilesList = [];

	/**
	 * Should I pormpt the user ot run the configuration wizard?
	 *
	 * @var  bool
	 */
	public $promptForBackupRestoration = false;

	/**
	 * Sorting order options
	 *
	 * @var  array
	 */
	public $sortFields = [];

	/**
	 * @var array
	 */
	protected $enginesPerProfile;

	/**
	 * The model state
	 *
	 * @since  1.6
	 * @var    Registry
	 */
	protected $state;

	/**
	 * @since 9.4.2
	 * @var   int|null
	 */
	private ?int $itemCount;

	public function display($tpl = null)
	{
		// Load custom Javascript for this page
		$this->getDocument()->getWebAssetManager()
		               ->useScript('com_akeebabackup.manage');

		$cParams = ComponentHelper::getParams('com_akeebabackup');

		$app               = Factory::getApplication();
		$user              = $app->getIdentity();
		$this->permissions = [
			'configure' => $user->authorise('akeebabackup.configure', 'com_akeebabackup'),
			'backup'    => $user->authorise('akeebabackup.backup', 'com_akeebabackup'),
			'download'  => $user->authorise('akeebabackup.download', 'com_akeebabackup'),
		];

		// Push translations to the frontend
		Text::script('COM_AKEEBABACKUP_BUADMIN_LABEL_REMOTEFILEMGMT');
		Text::script('COM_AKEEBABACKUP_REMOTEFILES_INPROGRESS_HEADER');

		/** @var ProfilesModel $profilesModel */
		$profilesModel           = $this->getModel('Profiles');
		$this->enginesPerProfile = $profilesModel->getPostProcessingEnginePerProfile();

		// "Show warning first" download button.
		Text::script('COM_AKEEBABACKUP_BUADMIN_LOG_DOWNLOAD_CONFIRM', false);
		$this->getDocument()
			->addScriptOptions('akeebabackup.Manage.baseURI', JUri::base())
			->addScriptOptions('akeebabackup.Manage.downloadURL', Route::_('index.php?option=com_akeebabackup&task=Manage.download&' . $app->getSession()->getFormToken() . '=1', false));

		/** @var StatisticsModel $model */
		$model               = $this->getModel('Statistics');
		$filters             = $this->getFilters();
		$ordering            = $this->getOrdering();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();
		$this->fixListLimitPastTotal($model, fn() => (int) Platform::getInstance()->get_statistics_count($filters));
		$this->items = $model->getStatisticsListWithMeta(false, $filters, $ordering);

		// Let's create an array indexed with the profile id for better handling
		$profilesModel->setState('filter.search', '');
		$profilesModel->setState('filter.quickicon', '');
		$profilesModel->setState('filter.quickicon', '');
		$profilesModel->setState('list.start', 0);
		$profilesModel->setState('list.limit', 0);
		$tempProfiles = $profilesModel->getItems();
		$profiles     = [];

		foreach ($tempProfiles as $profile)
		{
			$profiles[$profile->id] = $profile;
		}

		$profilesList = [
			HTMLHelper::_('select.option', '', '–' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_PROFILEID') . '–'),
		];

		if (!empty($profiles))
		{
			foreach ($profiles as $profile)
			{
				$profilesList[] = HTMLHelper::_('select.option', $profile->id, '#' . $profile->id . '. ' . $profile->description);
			}
		}

		// Assign data to the view
		$this->profiles     = $profiles; // Profiles
		$this->profilesList = $profilesList; // Profiles list for select box
		$this->itemCount    = count($this->items);
		$this->pagination   = $model->getFilteredPagination($filters); // Pagination object

		$this->frozenList = [
			HTMLHelper::_('select.option', '', '–' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_FROZEN_SELECT') . '–'),
			HTMLHelper::_('select.option', '1', Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_FROZEN_FROZEN')),
			HTMLHelper::_('select.option', '2', Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_FROZEN_UNFROZEN')),
		];

		// Initialise the timezone information and preferences about displaying the backup start time
		$this->initTimeInformation();

		// Should I show the prompt for the configuration wizard?
		$this->promptForBackupRestoration = $cParams->get('show_howtorestoremodal', 1) != 0;

		// Construct the array of sorting fields
		$this->sortFields = [
			'id'          => Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ID'),
			'description' => Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_DESCRIPTION'),
			'backupstart' => Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_START'),
			'profile_id'  => Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_PROFILEID'),
		];

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * File size formatting function. COnverts number of bytes to a human readable represenation.
	 *
	 * @param   int     $sizeInBytes         Size in bytes
	 * @param   int     $decimals            How many decimals should I use? Default: 2
	 * @param   string  $decSeparator        Decimal separator
	 * @param   string  $thousandsSeparator  Thousands grouping character
	 *
	 * @return string
	 */
	public function formatFilesize($sizeInBytes, $decimals = 2, $decSeparator = '.', $thousandsSeparator = '')
	{
		if ($sizeInBytes <= 0)
		{
			return '-';
		}

		$units = ['b', 'KB', 'MB', 'GB', 'TB'];
		$unit  = floor(log($sizeInBytes, 2) / 10);

		if ($unit == 0)
		{
			$decimals = 0;
		}

		return number_format($sizeInBytes / (1024 ** $unit), $decimals, $decSeparator, $thousandsSeparator) . ' ' . $units[$unit];
	}

	/**
	 * Returns the custom states for frozen/unfrozen records, for use with JGrid.state
	 *
	 * @return array[]
	 * @since  9.0.0
	 */
	public function getFrozenStates()
	{
		return [
			// Frozen record
			1 => [
				// Default toggle action is to unpublish (unfreeze) the record.
				'task'           => 'unpublish',
				// Ignored
				'text'           => '',
				// The tooltip reads "Unfreeze record"
				'active_title'   => 'COM_AKEEBABACKUP_BUADMIN_LABEL_ACTION_UNFREEZE',
				// Ignored (we don't do disabled state toggles in Akeeba Backup)
				'inactive_title' => '',
				// Show a tooltip, please
				'tip'            => true,
				// The x in the beginning prevents Joomla rendering its own icons. The rest is self-explanatory.
				'active_class'   => 'x text-primary border-primary fa fa-snowflake akeebabackup-icon-frozen',
				// Ignored (we don't do disabled state toggles in Akeeba Backup)
				'inactive_class' => 'x text-primary border-primary fa fa-snowflake akeebabackup-icon-frozen',
			],
			// Unfrozen record (DEFAULT STATE)
			0 => [
				// Default toggle action is to publish (freeze) the record.
				'task'           => 'publish',
				// Ignored
				'text'           => '',
				// The tooltip reads "Freeze record"
				'active_title'   => 'COM_AKEEBABACKUP_BUADMIN_LABEL_ACTION_FREEZE',
				// Ignored (we don't do disabled state toggles in Akeeba Backup)
				'inactive_title' => '',
				// Show a tooltip, please
				'tip'            => true,
				// The x in the beginning prevents Joomla rendering its own icons. The rest is self-explanatory.
				'active_class'   => 'x fa fa-tint',
				// Ignored (we don't do disabled state toggles in Akeeba Backup)
				'inactive_class' => 'x fa fa-tint',
			],
		];
	}

	/**
	 * Translates the internal backup type (e.g. cli) to a human readable string
	 *
	 * @param   string  $recordType  The internal backup type
	 *
	 * @return  string
	 */
	public function translateBackupType($recordType)
	{
		static $backup_types = null;

		if (!is_array($backup_types))
		{
			// Load a mapping of backup types to textual representation
			$scripting    = AkeebaFactory::getEngineParamsProvider()->loadScripting();
			$backup_types = [];

			foreach ($scripting['scripts'] as $key => $data)
			{
				$textKey            = str_replace('COM_AKEEBA_', 'COM_AKEEBABACKUP_', $data['text']);
				$backup_types[$key] = Text::_($textKey);
			}
		}

		if (array_key_exists($recordType, $backup_types))
		{
			return $backup_types[$recordType];
		}

		return '&ndash;';
	}

	/**
	 * Escapes backup comment to remove all tags, convert new-lines and finally convert HTML entities
	 *
	 * @param $comment
	 *
	 * @return string
	 */
	protected function escapeComment($comment)
	{
		if (!$comment)
		{
			return '';
		}

		$comment = strip_tags($comment);
		$comment = nl2br($comment);

		return $this->escape($comment);
	}

	/**
	 * Returns the origin's translated name and the appropriate icon class
	 *
	 * @param   array  $record  A backup record
	 *
	 * @return  array  array(originTranslation, iconClass)
	 */
	protected function getOriginInformation($record)
	{
		$originLanguageKey = 'COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN_' . $record['origin'];
		$originDescription = Text::_($originLanguageKey);

		switch (strtolower($record['origin']))
		{
			case 'backend':
				$originIcon = 'fa fa-desktop';
				break;

			case 'frontend':
				$originIcon = 'fa fa-globe';
				break;

			case 'json':
				$originIcon = 'fa fa-cloud';
				break;

			case 'joomlacli':
			case 'joomla':
				$originIcon = 'fa fab fa-joomla';
				break;

			case 'cli':
				$originIcon = 'fa fa-terminal';
				break;

			case 'xmlrpc':
				$originIcon = 'fa fa-code';
				break;

			case 'lazy':
				$originIcon = 'fa fa-cubes';
				break;

			default:
				$originIcon = 'fa fa-question';
				break;
		}

		if (empty($originLanguageKey) || ($originDescription == $originLanguageKey))
		{
			$originDescription = '&ndash;';
			$originIcon        = 'fa fa-question-circle';

			return [$originDescription, $originIcon];
		}

		return [$originDescription, $originIcon];
	}

	/**
	 * Get the profile name for the backup record (or "–" if the profile no longer exists)
	 *
	 * @param   array  $record  A backup record
	 *
	 * @return  string
	 */
	protected function getProfileName($record)
	{
		$profileName = '&mdash;';

		if (isset($this->profiles[$record['profile_id']]))
		{
			$profileName = $this->escape($this->profiles[$record['profile_id']]->description);

			return $profileName;
		}

		return $profileName;
	}

	/**
	 * Get the class and icon for the backup status indicator
	 *
	 * @param   array  $record  A backup record
	 *
	 * @return  array  array(class, icon)
	 */
	protected function getStatusInformation($record)
	{
		$statusClass = '';

		switch ($record['meta'])
		{
			case 'ok':
				$statusIcon  = 'fa fa-check-circle';
				$statusClass = 'bg-success';
				break;
			case 'pending':
				$statusIcon  = 'fa fa-play';
				$statusClass = 'bg-warning';
				break;
			case 'fail':
				$statusIcon  = 'fa fa-times';
				$statusClass = 'bg-danger';
				break;
			case 'remote':
				$statusIcon  = 'fa fa-cloud';
				$statusClass = 'bg-primary';
				break;
			default:
				$statusIcon  = 'fa fa-trash';
				$statusClass = 'bg-secondary';
				break;
		}

		return [$statusClass, $statusIcon];
	}

	private function addToolbar(): void
	{
		$user        = Factory::getApplication()->getIdentity();
		$permissions = [
			'configure' => $user->authorise('akeebabackup.configure', 'com_akeebabackup'),
			'backup'    => $user->authorise('akeebabackup.backup', 'com_akeebabackup'),
			'download'  => $user->authorise('akeebabackup.download', 'com_akeebabackup'),
		];

		// Get the toolbar object instance
		$toolbar = $this->getToolbarCompat();

		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_BUADMIN'), 'icon-akeeba');

		if (AKEEBABACKUP_PRO)
		{
			$toolbar->linkButton('discover', 'COM_AKEEBABACKUP_DISCOVER')
			        ->url(Uri::base() . 'index.php?option=com_akeebabackup&view=Discover')
			        ->icon('fa fa-file-import');
		}

		if ($permissions['configure'])
		{
			$dropdown = $toolbar->dropdownButton('status-group')
			                    ->text('JTOOLBAR_CHANGE_STATUS')
			                    ->toggleSplit(false)
			                    ->icon('icon-ellipsis-h')
			                    ->buttonClass('btn btn-action')
			                    ->listCheck(true);

			/** @var Toolbar $childBar */
			$childBar = $dropdown->getChildToolbar();

			$childBar->publish('manage.publish')
			         ->icon('fa fa-check-circle')
			         ->text('COM_AKEEBABACKUP_BUADMIN_LABEL_ACTION_FREEZE')
			         ->listCheck(true);

			$childBar->unpublish('manage.unpublish')
			         ->icon('fa fa-times-circle')
			         ->text('COM_AKEEBABACKUP_BUADMIN_LABEL_ACTION_UNFREEZE')
			         ->listCheck(true);

			if ($permissions['configure'] && AKEEBABACKUP_PRO)
			{
				$childBar->standardButton('restore', 'COM_AKEEBABACKUP_BUADMIN_LABEL_RESTORE', 'restore.main')
				         ->buttonClass('bg-warning')
				         ->icon('fa fa-history')
				         ->listCheck(true);
			}

			if ($permissions['download'] && AKEEBABACKUP_PRO)
			{
				$childBar->standardButton('transfer', 'COM_AKEEBABACKUP_BUADMIN_LABEL_TRANSFER', 'transfer.main')
				         ->icon('fa fa-truck')
				         ->listCheck(true);
			}

			$childBar->delete('manage.deletefiles')
			         ->icon('fa fa-broom')
			         ->text('COM_AKEEBABACKUP_BUADMIN_LABEL_DELETEFILES')
			         ->message('JGLOBAL_CONFIRM_DELETE')
			         ->listCheck(true);

			$childBar->delete('manage.delete')
			         ->message('JGLOBAL_CONFIRM_DELETE')
			         ->listCheck(true);
		}

		if ($permissions['backup'])
		{
			$toolbar->edit('statistic.edit');
		}

		$toolbar->back()
		        ->text('COM_AKEEBABACKUP_CONTROLPANEL')
		        ->icon('fa fa-' . (Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
		        ->url('index.php?option=com_akeebabackup');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/adminsiter-backup-files.html');
	}

	/**
	 * Get the filters in a format that Akeeba Engine understands
	 *
	 * @return  array
	 */
	private function getFilters()
	{
		$filters = [];
		$model   = $this->getModel('Statistics');

		if ($model->getState('filter.search'))
		{
			$filters[] = [
				'field'   => 'description',
				'operand' => 'LIKE',
				'value'   => $model->getState('filter.search'),
			];
		}

		$from = $model->getState('filter.from');
		$to   = $model->getState('filter.to');

		if ($from && $to)
		{
			$filters[] = [
				'field'   => 'backupstart',
				'operand' => 'BETWEEN',
				'value'   => $from,
				'value2'  => $to,
			];
		}
		elseif ($from)
		{
			$filters[] = [
				'field'   => 'backupstart',
				'operand' => '>=',
				'value'   => $from,
			];
		}
		elseif ($to)
		{
			$toDate = clone Factory::getDate($to);
			$to     = $toDate->format('Y-m-d') . ' 23:59:59';

			$filters[] = [
				'field'   => 'backupstart',
				'operand' => '<=',
				'value'   => $to,
			];
		}

		if ($model->getState('filter.origin'))
		{
			$filters[] = [
				'field'   => 'origin',
				'operand' => '=',
				'value'   => $model->getState('filter.origin'),
			];
		}

		if ($model->getState('filter.profile'))
		{
			$filters[] = [
				'field'   => 'profile_id',
				'operand' => '=',
				'value'   => (int) $model->getState('filter.profile'),
			];
		}

		$filterFrozen = $model->getState('filter.frozen');

		if (is_numeric($filterFrozen))
		{
			$filters[] = [
				'field'   => 'frozen',
				'operand' => '=',
				'value'   => intval($filterFrozen) === 2 ? 0 : 1,
			];
		}

		return $filters;
	}

	/**
	 * Get the list ordering in a format that Akeeba Engine understands
	 *
	 * @return  array
	 */
	private function getOrdering()
	{
		$model = $this->getModel('Statistics');

		return [
			'by'    => $model->getState('list.ordering') ?? 'id',
			'order' => $model->getState('list.direction') ?? 'DESC',
		];
	}
}