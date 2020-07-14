<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Discover as DiscoverModel;
use FOF30\Controller\Controller;
use JText;

class Discover extends Controller
{
	/**
	 * Discovers JPA, JPS and ZIP files in the selected profile's directory and
	 * lets you select them for inclusion in the import process.
	 */
	public function discover()
	{
		// CSRF prevention
		$this->csrfProtection();

		$directory = $this->input->get('directory', '', 'string');

		if (empty($directory))
		{
			$url = 'index.php?option=com_akeeba&view=Discover';
			$msg = JText::_('COM_AKEEBA_DISCOVER_ERROR_NODIRECTORY');
			$this->setRedirect($url, $msg, 'error');

			return;
		}

		/** @var DiscoverModel $model */
		$model = $this->getModel();
		$model->setState('directory', $directory);

		$this->display(false, false);
	}

	/**
	 * Performs the actual import
	 */
	public function import()
	{
		// CSRF prevention
		$this->csrfProtection();

		$directory = $this->input->get('directory', '', 'string');
		$files     = $this->input->get('files', array(), 'array');

		if (empty($files))
		{
			$url = 'index.php?option=com_akeeba&view=Discover';
			$msg = JText::_('COM_AKEEBA_DISCOVER_ERROR_NOFILESSELECTED');
			$this->setRedirect($url, $msg, 'error');

			return;
		}

		/** @var DiscoverModel $model */
		$model = $this->getModel();
		$model->setState('directory', $directory);

		foreach ($files as $file)
		{
			$model->import($file);
		}

		$url = 'index.php?option=com_akeeba&view=Manage';
		$msg = JText::_('COM_AKEEBA_DISCOVER_LABEL_IMPORTDONE');

		$this->setRedirect($url, $msg);
	}

}
