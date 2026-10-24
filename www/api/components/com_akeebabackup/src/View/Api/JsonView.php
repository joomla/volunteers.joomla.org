<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Api\View\Api;

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

class JsonView extends BaseJsonView
{
	public $data = null;

	/**
	 * Returns the JSON API response
	 *
	 * @param   string|null  $tpl  Ignored
	 *
	 * @return  void
	 * @throws  \Exception
	 * @since   9.6.0
	 */
	public function display($tpl = null)
	{
		if (!$this->data instanceof \Throwable)
		{
			$result = [
				'status' => 200,
				'data'   => $this->data,
			];
		}
		else
		{
			$result = [
				'status' => $this->data->getCode(),
				'data'   => $this->data->getMessage(),
			];

			// When site debugging is enabled AND error reporting is set to maximum we'll return exception traces
			$app               = Factory::getApplication();
			$siteDebug         = (bool) $app->get('debug');
			$maxErrorReporting = $app->get('error_reporting') === 'maximum';

			if ($siteDebug && $maxErrorReporting)
			{
				$result['debug'] = [];
				$thisException   = $this->data;

				while (!empty($thisException))
				{
					$result['debug'][] = [
						'message'   => $thisException->getMessage(),
						'code'      => $thisException->getCode(),
						'file'      => $thisException->getFile(),
						'line'      => $thisException->getLine(),
						'backtrace' => $thisException->getTrace(),
					];

					$thisException = $this->data->getPrevious();
				}
			}
		}

		$this->getDocument()->setBuffer(json_encode($result));

		echo $this->getDocument()->render();
	}
}