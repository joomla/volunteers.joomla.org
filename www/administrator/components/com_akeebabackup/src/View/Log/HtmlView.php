<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Log;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\LogModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewProfileIdAndNameTrait;
	use ViewToolbarTrait;

	/**
	 * Big log file threshold: 2Mb
	 */
	public const bigLogSize = 2097152;

	/**
	 * Size of the log file
	 *
	 * @var int
	 */
	public int $logSize = 0;

	/**
	 * Is the select log too big for being
	 *
	 * @var bool
	 */
	public bool $logTooBig = false;

	/**
	 * JHtml list of available log files
	 *
	 * @var  array
	 */
	public array $logs = [];

	/**
	 * Currently selected log file tag
	 *
	 * @var  string|null
	 */
	public ?string $tag = null;

	/**
	 * Shouldl I display a link to ALICE, the log analyser, in the interface?
	 *
	 * @since 9.4.4
	 * @var   bool
	 */
	public bool $hasAlice = false;

	/**
	 * The main page of the log viewer. It allows you to select a profile to display. When you do it displays the IFRAME
	 * with the actual log content and the button to download the raw log file.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Toolbar
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_LOG'), 'icon-akeeba');

		$toolbar = $this->getToolbarCompat();
		$toolbar->back()
		        ->text('COM_AKEEBABACKUP_CONTROLPANEL')
		        ->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
		        ->url('index.php?option=com_akeebabackup');
		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/view-log.html');

		// Load the view-specific Javascript
		$this->getDocument()->getWebAssetManager()
		               ->useScript('com_akeebabackup.log');

		// Get a list of log names
		/** @var LogModel $model */
		$model = $this->getModel();

		$this->logs = $model->getLogList();

		$tag = $model->getState('tag', '');

		if (empty($tag))
		{
			$tag = null;
		}

		$this->tag = $tag;

		// Let's check if the file is too big to display
		if ($this->tag)
		{
			$logFile = Utils::getLogFilePath($this->tag);

			if (!is_null($logFile))
			{
				$this->logSize   = filesize($logFile);
				$this->logTooBig = ($this->logSize >= self::bigLogSize);
			}

			$failedLogs     = $model->getLogFiles(true);
			$this->hasAlice = in_array($this->tag, $failedLogs);
		}

		if ($this->logTooBig)
		{
			$src = Uri::base() . 'index.php?option=com_akeebabackup&view=Log&task=inlineRaw&&tag=' . urlencode($this->tag) . '&tmpl=component';
			$this->getDocument()->addScriptOptions('akeeba.Log.iFrameSrc', $src);
		}

		$this->getProfileIdAndName();

		parent::display($tpl);
	}
}