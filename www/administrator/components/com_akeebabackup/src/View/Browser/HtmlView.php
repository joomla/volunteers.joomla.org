<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Browser;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Model\BrowserModel;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	/**
	 * Path to current folder (with variables such as [SITEROOT] replaced)
	 *
	 * @var  string
	 */
	public $folder = '';

	/**
	 * Path to current folder (WITHOUT variables such as [SITEROOT] replaced)
	 *
	 * @var  string
	 */
	public $folder_raw = '';

	/**
	 * Parent folder
	 *
	 * @var  string
	 */
	public $parent = '';

	/**
	 * Does the current folder exist in the filesystem?
	 *
	 * @var  bool
	 */
	public $exists = false;

	/**
	 * Is the current folder under the site's root directory? False means it's an off-site directory.
	 *
	 * @var  bool
	 */
	public $inRoot = false;

	/**
	 * Is the current folder restricted by open_basedir?
	 *
	 * @var  bool
	 */
	public $openbasedirRestricted = false;

	/**
	 * Is the current folder writable?
	 *
	 * @var  bool
	 */
	public $writable = false;

	/**
	 * Subdirectories
	 *
	 * @var  array
	 */
	public $subfolders = [];

	/**
	 * Breadcrumbs to display in the browser view
	 *
	 * @var  array
	 */
	public $breadcrumbs = [];

	public function display($tpl = null)
	{
		$wa = $this->getDocument()->getWebAssetManager();
		$wa->useScript('com_akeebabackup.browser');

		/** @var BrowserModel $model */
		$model = $this->getModel();

		// Pass the data from the model to the view template
		$this->folder                = $model->getState('folder', '');
		$this->folder_raw            = $model->getState('folder_raw', '');
		$this->parent                = $model->getState('parent', '');
		$this->exists                = (bool) $model->getState('exists', false);
		$this->inRoot                = (bool) $model->getState('inRoot', false);
		$this->openbasedirRestricted = (bool) $model->getState('openbasedirRestricted', false);
		$this->writable              = (bool) $model->getState('writable', false);
		$this->subfolders            = $model->getState('subfolders');
		$this->breadcrumbs           = $model->getState('breadcrumbs');

		parent::display($tpl);
	}
}