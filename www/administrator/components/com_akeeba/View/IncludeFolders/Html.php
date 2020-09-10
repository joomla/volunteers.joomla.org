<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\IncludeFolders;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Admin\Model\IncludeFolders;
use Akeeba\Backup\Admin\View\ViewTraits\ProfileIdAndName;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class Html extends BaseView
{
	use ProfileIdAndName;

	public function onBeforeMain()
	{
		$this->container->template->addJS('media://com_akeeba/js/Configuration.min.js', true, false, $this->container->mediaVersion);
		$this->container->template->addJS('media://com_akeeba/js/FileFilters.min.js', true, false, $this->container->mediaVersion);
		$this->container->template->addJS('media://com_akeeba/js/IncludeFolders.min.js', true, false, $this->container->mediaVersion);

		// Get a JSON representation of the directories data
		/** @var IncludeFolders $model */
		$model = $this->getModel();

		$platform = $this->container->platform;
		$platform->addScriptOptions('akeeba.System.params.AjaxURL', Uri::base() . 'index.php?option=com_akeeba&view=IncludeFolders&task=ajax');
		$platform->addScriptOptions('akeeba.Configuration.URLs', [
			'browser' => Uri::base() . 'index.php?option=com_akeeba&view=Browser&processfolder=1&tmpl=component&folder=',
		]);
		$platform->addScriptOptions('akeeba.IncludeFolders.guiData', $model->get_directories());

		$this->getProfileIdAndName();

		// Push translations
		Text::script('COM_AKEEBA_FILEFILTERS_LABEL_UIERRORFILTER');
	}
}
