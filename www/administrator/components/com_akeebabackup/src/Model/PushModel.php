<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Component\AkeebaBackup\Administrator\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\WebPush\WebPush\WebPush;
use Akeeba\WebPush\WebPushModelTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

if (!class_exists(WebPush::class))
{
	require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';
}

/**
 * A model to manage Push API subscriptions and notifications
 *
 * @since       9.3.1
 */
#[\AllowDynamicProperties]
class PushModel extends BaseDatabaseModel
{
	use WebPushModelTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		// This is required
		$this->initialiseWebPush('com_akeebabackup', 'vapidKey');
	}
}