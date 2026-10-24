<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Mixin;

// Protect from unauthorized access
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use DateInterval;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') || die();

/**
 * Provides the method to check whether front-end backup is enabled and weather the key is correct
 */
trait ControllerFrontEndPermissionsTrait
{
	private static $ENABLE_DATE_CHECKS = false;

	/**
	 * Check that the user has sufficient permissions to access the front-end backup feature.
	 *
	 * @return  void
	 */
	protected function checkPermissions()
	{
		// Is frontend backup enabled?
		$cParams = ComponentHelper::getParams('com_akeebabackup');
		$febEnabled = $cParams->get('legacyapi_enabled', 0) == 1;

		// Is the Secret Key strong enough?
		$validKey     = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		$validKeyTrim = trim($validKey);

		if (!Complexify::isStrongEnough($validKey, false))
		{
			$febEnabled = false;
		}

		if (static::$ENABLE_DATE_CHECKS && !$this->confirmDates())
		{
			@ob_end_clean();
			echo '402 Your version of Akeeba Backup is too old. Please update it to re-enable the remote backup features';
			flush();

			$this->app->close();
		}

		// Is the key good? Use a constant-time comparison to avoid leaking the Secret Word through timing attacks.
		$key = $this->input->get('key', '', 'raw');

		if (!$febEnabled || (empty($validKeyTrim)) || !hash_equals($validKey, (string) $key))
		{
			@ob_end_clean();
			echo sprintf("403 %s", Text::_('COM_AKEEBABACKUP_COMMON_ERR_NOT_ENABLED'));
			flush();

			$this->app->close();
		}
	}

	private function confirmDates()
	{
		if (!defined('AKEEBABACKUP_DATE'))
		{
			return false;
		}

		try
		{
			$jDate    = clone Factory::getDate(AKEEBABACKUP_DATE);
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
