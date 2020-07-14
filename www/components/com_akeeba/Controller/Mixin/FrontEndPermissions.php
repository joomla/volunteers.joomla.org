<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Controller\Mixin;

// Protect from unauthorized access
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use DateInterval;
use Exception;
use FOF30\Date\Date;
use JText;

defined('_JEXEC') or die();

/**
 * Provides the method to check whether front-end backup is enabled and weather the key is correct
 */
trait FrontEndPermissions
{
	/**
	 * Check that the user has sufficient permissions to access the front-end backup feature.
	 *
	 * @return  void
	 */
	protected function checkPermissions()
	{
		// Is frontend backup enabled?
		$febEnabled = $this->container->params->get('legacyapi_enabled', 0) == 1;

		// Is the Secret Key strong enough?
		$validKey     = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		$validKeyTrim = trim($validKey);

		if (!Complexify::isStrongEnough($validKey, false))
		{
			$febEnabled = false;
		}

		if (!$this->confirmDates())
		{
			@ob_end_clean();
			echo '402 Your version of Akeeba Backup is too old. Please update it to re-enable the remote backup features';
			flush();

			$this->container->platform->closeApplication();
		}

		// Is the key good?
		$key = $this->input->get('key', '', 'none', 2);

		if (!$febEnabled || ($key != $validKey) || (empty($validKeyTrim)))
		{
			@ob_end_clean();
			echo '403 ' . JText::_('COM_AKEEBA_COMMON_ERR_NOT_ENABLED');
			flush();

			$this->container->platform->closeApplication();
		}

	}

	private function confirmDates()
	{
		if (!defined('AKEEBA_DATE'))
		{
			return false;
		}

		try
		{
			$jDate    = new Date(AKEEBA_DATE);
			$interval = new DateInterval('P4M');
			$jFuture  = $jDate->add($interval);
			$futureTS = $jFuture->toUnix();
		}
		catch (Exception $e)
		{
			return false;
		}

		return time() <= $futureTS;
	}
}
