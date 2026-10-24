<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Profiles;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewListLimitFixTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTableUITrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfilesModel;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewTableUITrait;
	use ViewListLimitFixTrait;
	use ViewToolbarTrait;

	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  1.6
	 */
	public $activeFilters = [];

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  1.6
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  1.6
	 */
	protected $state;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   9.0.0
	 */
	public function display($tpl = null): void
	{
		$errors = [];

		try
		{
			/** @var ProfilesModel $model */
			$model = $this->getModel();
			$this->fixListLimitPastTotal($model);
			$this->items         = $model->getItems();
			$this->pagination    = $model->getPagination();
			$this->state         = $model->getState();
			$this->filterForm    = $model->getFilterForm();
			$this->activeFilters = $model->getActiveFilters();
		}
		catch (Exception $e)
		{
			$errors = [$e->getMessage()];
		}

		// Check for errors.
		if (method_exists($this->getModel(), 'getErrors'))
		{
			/** @noinspection PhpDeprecationInspection */
			$errors = $this->getModel()->getErrors();
		}

		if (
			(is_array($errors) || $errors instanceof \Countable)
				? count($errors)
				: 0
		)
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar(): void
	{
		$user = Factory::getApplication()->getIdentity();

		// Get the toolbar object instance
		$toolbar = $this->getToolbarCompat();

		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_PROFILES'), 'icon-akeeba');

		$toolbar->addNew('profile.add');

		$toolbar->standardButton('copy', 'COM_AKEEBABACKUP_LBL_BATCH_COPY', 'profiles.copy')
			->listCheck(true);

		if ($user->authorise('akeebabackup.configure', 'com_akeebabackup'))
		{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			$childBar->publish('profiles.publish')
				->icon('fa fa-check-circle')
				->text('COM_AKEEBABACKUP_PROFILES_BTN_PUBLISH')
				->listCheck(true);

			$childBar->unpublish('profiles.unpublish')
				->icon('fa fa-times-circle')
				->text('COM_AKEEBABACKUP_PROFILES_BTN_UNPUBLISH')
				->listCheck(true);

			$childBar->standardButton('reset', 'COM_AKEEBABACKUP_PROFILES_BTN_RESET', 'profiles.reset')
				->icon('fa fa-radiation')
				->listCheck(true);

			$childBar->delete('profiles.delete')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		}

		$toolbar->linkButton('import', 'COM_AKEEBABACKUP_PROFILES_IMPORT', '')
			->icon('fa fa-upload')
			->attributes([
				'data-bs-toggle' => 'modal',
				'data-bs-target' => '#importModal',
			])
			->url('#')
		;

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		if ($user->authorise('core.admin', 'com_akeebabackup') || $user->authorise('core.options', 'com_akeebabackup'))
		{
			$toolbar->preferences('com_akeebabackup');
		}

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/using-basic-operations.html#profiles-management');
	}
}