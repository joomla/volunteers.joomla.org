<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerAjaxTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerProfileAccessTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerProfileRestrictionTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Joomla\CMS\MVC\Controller\BaseController;

class DatabasefiltersController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait
	{
		ControllerCustomACLTrait::onBeforeExecute as onBeforeExecuteACL;
	}
	use ControllerProfileRestrictionTrait
	{
		ControllerProfileRestrictionTrait::onBeforeExecute as onBeforeExecuteRestrictedProfile;
	}
	use ControllerReusableModelsTrait;
	use ControllerAjaxTrait;
	use ControllerProfileAccessTrait;

	protected function onBeforeExecute(&$task)
	{
		$this->onBeforeExecuteACL($task);
		$this->onBeforeExecuteRestrictedProfile($task);
	}
}