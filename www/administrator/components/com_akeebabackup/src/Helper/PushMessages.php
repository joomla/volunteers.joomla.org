<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Model\PushModel;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\PushMessagesInterface;
use Akeeba\WebPush\NotificationOptions;
use Akeeba\WebPush\WebPush\MessageSentReport;
use Akeeba\WebPush\WebPush\WebPush;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

/**
 * A replacement push notifications helper for the Akeeba Engine. This one is Web Push API aware.
 *
 * @since       9.3.1
 */
class PushMessages implements PushMessagesInterface
{
	/**
	 * @since  9.3.1
	 * @var    \Joomla\CMS\MVC\Factory\MVCFactoryInterface
	 */
	public static $mvcFactory;

	/**
	 * User IDs who have Web Push enabled
	 *
	 * @since  9.3.1
	 * @var    array|null
	 */
	private static $webPushUsers = null;

	/**
	 * The classic push messages handler
	 *
	 * @since  9.3.1
	 * @var    \Akeeba\Engine\Util\PushMessages|null
	 */
	private $corePush = null;

	private $hasWebPush = false;

	/**
	 * Public constructor.
	 *
	 * Decides which push method to use.
	 *
	 * @since   9.3.1
	 */
	public function __construct()
	{
		$pushPreference   = Platform::getInstance()->get_platform_configuration_option('push_preference', '0');
		$this->hasWebPush = class_exists(WebPush::class);

		/**
		 * Fall back to the classic push handler if we're not using Web Push or if the integration is not available.
		 */
		if (!$this->hasWebPush || $pushPreference !== 'webpush')
		{
			$this->corePush = new \Akeeba\Engine\Util\PushMessages();
		}
	}

	/**
	 * Sends a push message, containing a URL/URI, to all connected devices. The URL will be rendered as something
	 * clickable on most devices.
	 *
	 * @param   string  $url      The URL/URI
	 * @param   string  $subject  The subject of the message, shown in the lock screen. Keep it short.
	 * @param   string  $details  Long(er) description of what the message is about. Plain text (no HTML).
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	public function link($url, $subject, $details = null)
	{
		if (is_object($this->corePush))
		{
			$this->corePush->link($url, $subject, $details);

			return;
		}

		if (!$this->hasWebPush)
		{
			return;
		}

		$details = $details ?? '';
		$details .= empty($details) ? '' : ' ';

		$this->message($subject, $details . $url);
	}

	/**
	 * Sends a push message to all connected devices. The intent is to provide the user with an information message,
	 * e.g. notify them about the progress of the backup.
	 *
	 * @param   string  $subject  The subject of the message, shown in the lock screen. Keep it short.
	 * @param   string  $details  Long(er) description of what the message is about. Plain text (no HTML).
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	public function message($subject, $details = null)
	{
		if (is_object($this->corePush))
		{
			$this->corePush->message($subject, $details);

			return;
		}

		if (!$this->hasWebPush)
		{
			return;
		}

		$logger = \Akeeba\Engine\Factory::getLog();

		$model = $this->getPushModel();

		if (empty($model))
		{
			$logger->notice('[Web Push] Cannot get the Web Push model or no push users are subscribed; aborted');

			return;
		}

		$options      = new NotificationOptions();
		$options->tag = 'com_akeebabackup';

		if ($details)
		{
			$options->body = $details;
		}

		foreach ($this->getWebPushUsers() as $uid)
		{
			$logger->debug(sprintf('[Web Push] Notifying user ID %d', $uid));

			try
			{
				$reports = $model->sendNotification($subject, $options->toArray(), $uid);
			}
			catch (\ErrorException $e)
			{
				$logger->notice(sprintf('[Web Push] PHP Error: %s', $e->getMessage()));
			}

			$failed = 0;
			$total  = 0;

			/** @var MessageSentReport $report */
			foreach ($reports as $report)
			{
				$total++;

				if (!$report->isSuccess())
				{
					$failed++;

					$logger->debug(sprintf('[Web Push] Partial failure: %s', $report->getReason()));
				}
			}

			if ($failed === $total)
			{
				$logger->debug(sprintf('[Web Push] Failed to notify user ID %s on %d subscription(s)', $uid, $total));
			}

			if ($failed > 0)
			{
				$logger->debug(sprintf('[Web Push] Partially notified user ID %d on %d subscription(s), %d of which failed', $uid, $total, $failed));
			}
			else
			{
				$logger->debug(sprintf('[Web Push] Successfully notified user ID %d on %d subscription(s)', $uid, $total));
			}
		}
	}

	/**
	 * Get the PushModel object, if possible
	 *
	 * @return  PushModel|null
	 *
	 * @since   9.3.1
	 */
	private function getPushModel(): ?PushModel
	{
		if (empty(self::$mvcFactory))
		{
			return null;
		}

		$userIDs = $this->getWebPushUsers();

		if (empty($userIDs))
		{
			return null;
		}

		try
		{
			/** @var PushModel|null $model */
			$model = self::$mvcFactory->createModel('Push', 'Administrator');
		}
		catch (\Throwable $e)
		{
			return null;
		}

		if (!is_object($model) || !method_exists($model, 'sendNotification'))
		{
			return null;
		}

		return $model;
	}

	/**
	 * Get the user IDs which should be receiving push messages.
	 *
	 * @return  int[]
	 *
	 * @since   9.3.1
	 */
	private function getWebPushUsers(): array
	{
		if (!is_null(self::$webPushUsers))
		{
			return self::$webPushUsers;
		}

		self::$webPushUsers = [];

		try
		{
			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			            ->select($db->quoteName('user_id'))
			            ->from($db->quoteName('#__user_profiles'))
			            ->where($db->quoteName('profile_key') . ' = ' . $db->quote('com_akeebabackup.webPushSubscription'));

			self::$webPushUsers = array_filter(
				$db->setQuery($query)->loadColumn() ?: [],
				function ($uid) {
					/** @var User|null; $user */
					$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($uid);

					return ($user instanceof User)
						&& $user->authorise('core.manage', 'com_akeebabackup');
				}
			);
		}
		catch (\Exception $e)
		{
			// Ignore errors. We just don't send push messages.
		}

		return self::$webPushUsers;
	}
}