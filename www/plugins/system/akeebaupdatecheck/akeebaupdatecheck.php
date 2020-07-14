<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use FOF30\Date\Date;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;

defined('_JEXEC') || die();

// PHP version check
if (!version_compare(PHP_VERSION, '7.1.0', '>='))
{
	return;
}

class plgSystemAkeebaupdatecheck extends CMSPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @since       2.5
	 */
	public function __construct(&$subject, $config)
	{
		/**
		 * I know that this piece of code cannot possibly be executed since I have already returned BEFORE declaring
		 * the class when eAccelerator is detected. However, eAccelerator is a GINORMOUS, STINKY PILE OF BULL CRAP. The
		 * stupid thing will return above BUT it will also declare the class EVEN THOUGH according to how PHP works
		 * this part of the code should be unreachable o_O Therefore I have to define this constant and exit the
		 * constructor when we have already determined that this class MUST NOT be defined. Because screw you
		 * eAccelerator, that's why.
		 */
		if (defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			return;
		}

		parent::__construct($subject, $config);
	}

	public function onAfterInitialise()
	{
		// Make sure Akeeba Backup is installed
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_akeeba'))
		{
			return;
		}

		// Make sure Akeeba Backup is enabled
		if (!ComponentHelper::isEnabled('com_akeeba'))
		{
			return;
		}

		// Load FOF. Required for the Date class.
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			throw new RuntimeException('FOF 3.0 is not installed', 500);
		}

		// Do we have to run (at most once per 3 hours)?
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('lastupdate'))
			->from($db->qn('#__ak_storage'))
			->where($db->qn('tag') . ' = ' . $db->q('akeebaupdatecheck_lastrun'));

		$last = $db->setQuery($query)->loadResult();

		if (intval($last))
		{
			$last = new Date($last);
			$last = $last->toUnix();
		}
		else
		{
			$last = 0;
		}

		$now = time();

		if (!defined('AKEEBAUPDATECHECK_DEBUG') && (abs($now - $last) < 86400))
		{
			return;
		}

		// Use a 20% chance of running; this allows multiple concurrent page
		// requests to not cause double update emails being sent out.
		$random = rand(1, 5);

		if (!defined('AKEEBAUPDATECHECK_DEBUG') && ($random != 3))
		{
			return;
		}

		$now = new Date($now);

		// Update last run status
		// If I have the time of the last run, I can update, otherwise insert
		if ($last)
		{
			$query = $db->getQuery(true)
				->update($db->qn('#__ak_storage'))
				->set($db->qn('lastupdate') . ' = ' . $db->q($now->toSql()))
				->where($db->qn('tag') . ' = ' . $db->q('akeebaupdatecheck_lastrun'));
		}
		else
		{
			$query = $db->getQuery(true)
				->insert($db->qn('#__ak_storage'))
				->columns([$db->qn('tag'), $db->qn('lastupdate')])
				->values($db->q('akeebaupdatecheck_lastrun') . ', ' . $db->q($now->toSql()));
		}

		try
		{
			$result = $db->setQuery($query)->execute();
		}
		catch (Exception $exc)
		{
			$result = false;
		}

		if (!$result)
		{
			return;
		}

		// Load the container
		$container = FOF30\Container\Container::getInstance('com_akeeba');

		/** @var \Akeeba\Backup\Admin\Model\Updates $model */
		$model      = $container->factory->model('Updates')->tmpInstance();
		$updateInfo = $model->getUpdates();

		if (!$updateInfo['hasUpdate'])
		{
			return;
		}

		$superAdmins     = [];
		$superAdminEmail = $this->params->get('email', '');

		if (!empty($superAdminEmail))
		{
			$superAdmins = $this->getSuperUsers($superAdminEmail);
		}

		if (empty($superAdmins))
		{
			$superAdmins = $this->getSuperUsers();
		}

		if (empty($superAdmins))
		{
			return;
		}

		foreach ($superAdmins as $sa)
		{
			$model->sendNotificationEmail($updateInfo['version'], $sa->email);
		}
	}

	/**
	 * Returns the Super Users' email information. If you provide a comma separated $email list we will check that these
	 * emails do belong to Super Users and that they have not blocked reception of system emails.
	 *
	 * @param   null|string  $email  A list of Super Users to email, null for all Super Users
	 *
	 * @return  User[]  The list of Super User objects
	 */
	private function getSuperUsers($email = null)
	{
		// Convert the email list to an array
		$emails = [];

		if (!empty($email))
		{
			$temp   = explode(',', $email);
			$emails = [];

			foreach ($temp as $entry)
			{
				$emails[] = trim($entry);
			}

			$emails = array_unique($emails);
			$emails = array_map('strtolower', $emails);
		}

		// Get all usergroups with Super User access
		$db     = JFactory::getDbo();
		$q      = $db->getQuery(true)
			->select([$db->qn('id')])
			->from($db->qn('#__usergroups'));
		$groups = $db->setQuery($q)->loadColumn();

		// Get the groups that are Super Users
		$groups = array_filter($groups, function ($gid) {
			return Access::checkGroup($gid, 'core.admin');
		});

		$userList = [];

		foreach ($groups as $gid)
		{
			$uids = Access::getUsersByGroup($gid);

			array_walk($uids, function ($uid, $index) use (&$userList) {
				$userList[$uid] = JFactory::getUser($uid);
			});
		}

		if (empty($emails))
		{
			return $userList;
		}

		array_filter($userList, function (User $user) use ($emails) {
			return in_array(strtolower($user->email), $emails);
		});

		return $userList;
	}
}
