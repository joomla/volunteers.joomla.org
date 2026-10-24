<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseModel;

trait ControllerAjaxTrait
{
	protected $decodeJsonAsArray = false;

	public function ajax()
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		// Parse the JSON data and reset the action query param to the resulting array
		$action_json = $this->input->get('action', '', 'raw');
		$action      = json_decode($action_json, $this->decodeJsonAsArray);

		/** @var BaseModel $model */
		$model = $this->getModel($this->getName(), 'Administrator');

		$model->setState('action', $action);

		$ret = $model->doAjax();

		@ob_end_clean();
		echo '###' . json_encode($ret) . '###';

		if (ComponentHelper::getParams('com_akeebabackup')->get('no_flush', 0) != 1)
		{
			flush();
		}

		$this->app->close();
	}

}