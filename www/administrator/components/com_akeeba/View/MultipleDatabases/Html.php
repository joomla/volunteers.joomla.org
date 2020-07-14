<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\MultipleDatabases;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\MultipleDatabases;
use Akeeba\Backup\Admin\View\ViewTraits\ProfileIdAndName;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Uri\Uri;

/**
 * View for database table exclusion
 */
class Html extends BaseView
{
	use ProfileIdAndName;

	/**
	 * Main page
	 */
	public function onBeforeMain()
	{
		// Load Javascript files
		$this->container->template->addJS('media://com_akeeba/js/FileFilters.min.js');
		$this->container->template->addJS('media://com_akeeba/js/MultipleDatabases.min.js');

		/** @var MultipleDatabases $model */
		$model = $this->getModel();

		$this->getProfileIdAndName();

		// Push translations
		JText::script('COM_AKEEBA_MULTIDB_GUI_LBL_LOADING');
		JText::script('COM_AKEEBA_MULTIDB_GUI_LBL_CONNECTOK');
		JText::script('COM_AKEEBA_MULTIDB_GUI_LBL_CONNECTFAIL');
		JText::script('COM_AKEEBA_MULTIDB_GUI_LBL_SAVEFAIL');
		JText::script('COM_AKEEBA_MULTIDB_GUI_LBL_LOADING');

		$platform = $this->container->platform;
		$platform->addScriptOptions('akeeba.System.params.AjaxURL', Uri::base() . 'index.php?option=com_akeeba&view=MultipleDatabases&task=ajax');
		$platform->addScriptOptions('akeeba.Multidb.loadingGif', $this->container->template->parsePath('media://com_akeeba/icons/loading.gif'));
		$platform->addScriptOptions('akeeba.Multidb.guiData', $model->get_databases());
	}
}
