<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use RuntimeException;

/**
 * The single source of truth for Akeeba Backup's custom ACL privilege map.
 *
 * The map is keyed by the backend's `view` and `view.task` identifiers. It is consumed by the backend controllers
 * (through ControllerCustomACLTrait) and by the JSON API, where each API method declares the `view.task` key of the
 * backend operation it is equivalent to. Keeping a single map means an API method and its backend counterpart can
 * never drift apart in terms of the privilege they require.
 *
 * @since 10.4.0
 */
trait AkeebaBackupACLTrait
{
	/**
	 * Checks if the currently logged in user has the required ACL privileges for a view and task. If not, a
	 * RuntimeException is thrown.
	 *
	 * @param   string|null  $view  The view to check against
	 * @param   string|null  $task  The task to check against
	 *
	 * @return  void
	 * @throws  RuntimeException  When the user is not authorised
	 */
	protected function akeebaBackupACLCheck($view, $task)
	{
		$privilege = $this->getAkeebaBackupRequiredPrivilege($view, $task);

		// If an empty privilege is defined do not perform any ACL checks
		if (empty($privilege))
		{
			return;
		}

		$user = Factory::getApplication()->getIdentity() ?? (new User());

		if (!$user->authorise($privilege, 'com_akeebabackup'))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Returns the ACL privilege required to access a view and task.
	 *
	 * Anything which is not explicitly listed in the map requires the most restrictive of the component's own
	 * privileges, akeebabackup.configure. This is deliberate: a view — or a JSON API method — added without a
	 * corresponding map entry fails closed instead of being left wide open.
	 *
	 * @param   string|null  $view  The view to check against
	 * @param   string|null  $task  The task to check against
	 *
	 * @return  string  The required privilege; an empty string means "no ACL check"
	 */
	protected function getAkeebaBackupRequiredPrivilege($view, $task): string
	{
		// Akeeba Backup-specific ACL checks. All views not listed here are limited by the akeeba.configure privilege.
		$viewACLMap = [
			'controlpanel'       => 'core.manage',
			'backup'             => 'akeebabackup.backup',
			'manage'             => 'core.manage',
			'manage.download'    => 'akeebabackup.download',
			'manage.remove'      => 'akeebabackup.download',
			'manage.delete'      => 'akeebabackup.download',
			'manage.deletefiles' => 'akeebabackup.download',
			'manage.showcomment' => 'akeebabackup.backup',
			'manage.save'        => 'akeebabackup.download',
			'manage.restore'     => 'akeebabackup.configure',
			'manage.cancel'      => 'akeebabackup.backup',
			'statistic'          => 'akeebabackup.download',
			'upload'             => 'akeebabackup.backup',
			'remotefiles'        => 'akeebabackup.download',
			'transfer'           => 'akeebabackup.download',
			// The Schedule page echoes the front-end/JSON API secrets, so it needs the same access as editing the
			// component's Options (core.admin).
			'schedule'           => 'core.admin',
			// The Push view must be accessible to anyone who can access Akeeba Backup (core.manage) plus anti-CSRF.
			'push'               => 'core.manage',
		];

		$view = strtolower($view ?? 'controlpanel');
		$task = strtolower($task ?? 'main');

		// Default
		$privilege = 'akeebabackup.configure';

		// Just the view was found
		if (array_key_exists($view, $viewACLMap))
		{
			$privilege = $viewACLMap[$view];
		}

		// The view AND task was found
		if (array_key_exists($view . '.' . $task, $viewACLMap))
		{
			$privilege = $viewACLMap[$view . '.' . $task];
		}

		return $privilege;
	}
}
