<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Component\AkeebaBackup\Administrator\Controller
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\WebPush\NotificationOptions;
use Akeeba\WebPush\WebPush\WebPush;
use Akeeba\WebPush\WebPushControllerTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

if (!class_exists(WebPush::class))
{
	require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';
}

/**
 * A controller to manage the Push API subscriptions
 *
 * @since       9.3.1
 */
class PushController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use WebPushControllerTrait;

	/** @inheritDoc */
	public function getModel($name = 'Push', $prefix = 'Administrator', $config = [])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Sends an example push notification on push subscription to let the user know everything works.
	 *
	 * @param   object|null  $subscription  The push subscription we just registered into the database
	 *
	 * @since   9.3.1
	 */
	protected function onAfterWebPushSaveSubscription(?object $subscription)
	{
		$siteName = $this->app->get('sitename');

		$title = Text::sprintf('COM_AKEEBABACKUP_CONTROLPANEL_WEBPUSH_HELLO_TITLE', $siteName);
		$options = new NotificationOptions();
		$options->body = Text::_('COM_AKEEBABACKUP_CONTROLPANEL_WEBPUSH_HELLO_BODY');

		$this->getModel()->sendNotification(
			$title,
			$options->toArray(),
			null,
			$subscription
		);
	}
}