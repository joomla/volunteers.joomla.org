<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\Restore;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Restore;
use Akeeba\Engine\Platform;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Uri\Uri as JUri;

class Html extends BaseView
{
	public $id;
	public $ftpparams;
	public $extractionmodes;
	public $extension;

	protected function onBeforeMain()
	{
		$this->loadCommonJavascript();

		/** @var Restore $model */
		$model = $this->getModel();

		$this->id              = $model->getState('id', '', 'int');
		$this->ftpparams       = $this->getFTPParams();
		$this->extractionmodes = $this->getExtractionModes();

		$backup          = Platform::getInstance()->get_statistics($this->id);
		$this->extension = strtolower(substr($backup['absolute_path'], -3));

		$platform = $this->container->platform;
		$platform->addScriptOptions('akeeba.Configuration.URLs', [
			'browser'    => 'index.php?view=Browser&tmpl=component&processfolder=1&folder=',
			'ftpBrowser' => 'index.php?option=com_akeeba&view=FTPBrowser',
			'testFtp'    => 'index.php?option=com_akeeba&view=Restore&task=ajax&ajax=testftp',
		]);
	}

	protected function onBeforeStart()
	{
		$this->loadCommonJavascript();

		/** @var Restore $model */
		$model = $this->getModel();

		$this->setLayout('restore');

		// Pass script options
		$adminRootUrl = rtrim(JUri::base(), '/');
		$platform     = $this->container->platform;

		$platform->addScriptOptions('akeeba.Restore.password', $model->getState('password'));
		$platform->addScriptOptions('akeeba.Restore.ajaxURL', $adminRootUrl . '/components/com_akeeba/restore.php');
		$platform->addScriptOptions('akeeba.Restore.mainURL', $adminRootUrl . '/index.php');
		$platform->addScriptOptions('akeeba.Restore.inMainRestoration', true);
	}

	/**
	 * Returns the available extraction modes for use by JHtml
	 *
	 * @return  array
	 */
	private function getExtractionModes()
	{
		$options   = [];
		$options[] = JHtml::_('select.option', 'hybrid', JText::_('COM_AKEEBA_RESTORE_LABEL_EXTRACTIONMETHOD_HYBRID'));
		$options[] = JHtml::_('select.option', 'direct', JText::_('COM_AKEEBA_RESTORE_LABEL_EXTRACTIONMETHOD_DIRECT'));
		$options[] = JHtml::_('select.option', 'ftp', JText::_('COM_AKEEBA_RESTORE_LABEL_EXTRACTIONMETHOD_FTP'));

		return $options;
	}

	/**
	 * Returns the FTP parameters from the Global Configuration
	 *
	 * @return  array
	 */
	private function getFTPParams()
	{
		$config = $this->container->platform->getConfig();

		return [
			'procengine' => $config->get('ftp_enable', 0) ? 'hybrid' : 'direct',
			'ftp_host'   => $config->get('ftp_host', 'localhost'),
			'ftp_port'   => $config->get('ftp_port', '21'),
			'ftp_user'   => $config->get('ftp_user', ''),
			'ftp_pass'   => $config->get('ftp_pass', ''),
			'ftp_root'   => $config->get('ftp_root', ''),
			'tempdir'    => $config->get('tmp_path', ''),
		];
	}

	private function loadCommonJavascript()
	{
		$this->container->template->addJS('media://com_akeeba/js/Configuration.min.js');
		$this->container->template->addJS('media://com_akeeba/js/Restore.min.js');

		// Push translations
		JText::script('COM_AKEEBA_CONFIG_UI_BROWSE');
		JText::script('COM_AKEEBA_CONFIG_UI_CONFIG');
		JText::script('COM_AKEEBA_CONFIG_UI_REFRESH');
		JText::script('COM_AKEEBA_FILEFILTERS_LABEL_UIROOT');
		JText::script('COM_AKEEBA_CONFIG_UI_FTPBROWSER_TITLE');
		JText::script('COM_AKEEBA_CONFIG_DIRECTFTP_TEST_OK');
		JText::script('COM_AKEEBA_CONFIG_DIRECTFTP_TEST_FAIL');
		JText::script('COM_AKEEBA_CONFIG_DIRECTSFTP_TEST_OK');
		JText::script('COM_AKEEBA_CONFIG_DIRECTSFTP_TEST_FAIL');
		JText::script('COM_AKEEBA_BACKUP_TEXT_LASTRESPONSE');
	}
}
