<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Log;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\Model\LogModel;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

#[\AllowDynamicProperties]
class RawView extends BaseHtmlView
{
	/**
	 * Currently selected log file tag
	 *
	 * @var  string
	 */
	public $tag;

	/**
	 * Renders the actual log content, for use in the IFRAME
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		/** @var LogModel $model */
		$model = $this->getModel();
		$tag   = $model->getState('tag', '');

		if (empty($tag))
		{
			$tag = null;
		}

		$this->tag = $tag;

		$this->setLayout('raw');

		parent::display($tpl);
	}


}