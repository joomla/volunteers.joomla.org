<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\RegExDatabaseFilters;

// Protect from unauthorized access
use Akeeba\Backup\Admin\Model\DatabaseFilters;
use Akeeba\Backup\Admin\Model\RegExDatabaseFilters;
use Akeeba\Backup\Admin\View\ViewTraits\ProfileIdAndName;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Uri\Uri as JUri;

defined('_JEXEC') or die();

class Html extends \FOF30\View\DataView\Html
{
	use ProfileIdAndName;

	/**
	 * SELECT element for choosing a database root
	 *
	 * @var  string
	 */
	public $root_select = '';

	/**
	 * List of database roots
	 *
	 * @var  array
	 */
	public $roots = [];

	/**
	 * Main page
	 */
	public function onBeforeMain()
	{
		// Load Javascript files
		$this->container->template->addJS('media://com_akeeba/js/FileFilters.min.js');
		$this->container->template->addJS('media://com_akeeba/js/RegExDatabaseFilters.min.js');

		/** @var RegExDatabaseFilters $model */
		$model = $this->getModel();

		/** @var DatabaseFilters $dbFilterModel */
		$dbFilterModel = $this->getModel('DatabaseFilters');

		// Get a JSON representation of the available roots
		$root_info = $dbFilterModel->get_roots();
		$roots     = [];
		$options   = [];

		if (!empty($root_info))
		{
			// Loop all dir definitions
			foreach ($root_info as $def)
			{
				$roots[]   = $def->value;
				$options[] = JHtml::_('select.option', $def->value, $def->text);
			}
		}

		$site_root         = '[SITEDB]';
		$this->root_select = JHtml::_('select.genericlist', $options, 'root', [
			'list.select' => $site_root,
			'id'          => 'active_root',
		]);
		$this->roots       = $roots;

		// Pass script options
		$platform = $this->container->platform;
		$platform->addScriptOptions('akeeba.System.params.AjaxURL', JUri::base() . 'index.php?option=com_akeeba&view=RegExDatabaseFilters&task=ajax');
		$platform->addScriptOptions('akeeba.RegExDatabaseFilters.guiData', $model->get_regex_filters($site_root));

		$this->getProfileIdAndName();

		// Push translations
		JText::script('COM_AKEEBA_FILEFILTERS_LABEL_UIROOT');
		JText::script('COM_AKEEBA_FILEFILTERS_LABEL_UIERRORFILTER');
		JText::script('COM_AKEEBA_DBFILTER_TYPE_REGEXTABLES');
		JText::script('COM_AKEEBA_DBFILTER_TYPE_REGEXTABLEDATA');
	}
}
