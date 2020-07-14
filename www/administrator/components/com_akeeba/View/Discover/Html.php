<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\Discover;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Discover;
use Akeeba\Engine\Factory;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text as JText;

class Html extends BaseView
{
	/**
	 * The directory we are currently listing
	 *
	 * @var  string
	 */
	public $directory;

	/**
	 * The list of importable archive files in the current directory
	 *
	 * @var  array
	 */
	public $files;

	public function onBeforeMain()
	{
		$this->container->template->addJS('media://com_akeeba/js/Configuration.min.js');
		$this->container->template->addJS('media://com_akeeba/js/Discover.min.js');

		/** @var Discover $model */
		$model = $this->getModel();

		$directory       = $model->getState('directory', '', 'path');
		$this->directory = $directory;

		if (empty($directory))
		{
			$config          = Factory::getConfiguration();
			$this->directory = $config->get('akeeba.basic.output_directory', '[DEFAULT_OUTPUT]');
		}

		// Push translations
		JText::script('COM_AKEEBA_CONFIG_UI_BROWSE');
		JText::script('COM_AKEEBA_FILEFILTERS_LABEL_UIROOT');

		$this->container->platform->addScriptOptions('akeeba.Discover.URLs.browser', 'index.php?option=com_akeeba&view=Browser&processfolder=1&tmpl=component&folder=');
	}

	public function onBeforeDiscover()
	{
		/** @var Discover $model */
		$model = $this->getModel();

		$directory = $model->getState('directory', '', 'path');
		$this->setLayout('discover');

		$files = $model->getFiles();

		$this->files     = $files;
		$this->directory = $directory;
	}
}
