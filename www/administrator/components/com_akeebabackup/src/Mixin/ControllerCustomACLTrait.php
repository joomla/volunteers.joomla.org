<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') or die;

/**
 * Applies Akeeba Backup's custom ACL privilege map to a backend controller.
 *
 * The map itself lives in AkeebaBackupACLTrait, which is shared with the JSON API.
 */
trait ControllerCustomACLTrait
{
	use AkeebaBackupACLTrait;

	protected function onBeforeExecute(&$task)
	{
		$this->akeebaBackupACLCheck($this->getName(), $this->task);
	}
}
