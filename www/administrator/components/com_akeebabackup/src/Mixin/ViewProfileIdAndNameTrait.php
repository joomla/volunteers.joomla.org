<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die;

use Akeeba\Engine\Platform;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

trait ViewProfileIdAndNameTrait
{
	/**
	 * Active profile ID
	 *
	 * @var  int
	 */
	public $profileId = 0;

	/**
	 * Active profile's description
	 *
	 * @var  string
	 */
	public $profileName = '';

	/**
	 * Is this profile available as an One Click Backup icon? 0/1
	 *
	 * @var  int
	 */
	public $quickIcon = 0;

	/**
	 * Find the currently active profile ID and name and put them in properties accessible by the view template
	 */
	protected function getProfileIdAndName()
	{
		$profileId     = (int) Platform::getInstance()->get_active_profile();

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('description'),
				$db->qn('quickicon')
			])->from($db->qn('#__akeebabackup_profiles'))
			->where($db->qn('id') . ' = :id')
			->bind(':id', $profileId, ParameterType::INTEGER);


		try
		{
			// Try to load the given profile
			$profile = $db->setQuery($query)->loadObject();

			$this->profileId   = $profileId;
			$this->profileName = $profile->description;
			$this->quickIcon   = $profile->quickicon;
		}
		catch (\Exception $e)
		{
			// If the default profile is not found fake it
			if ($profileId <= 1)
			{
				$this->profileId   = 1;
				$this->profileName = 'Default backup profile';
				$this->quickIcon   = 1;

				return;
			}

			// If the non-default profile is not found fall back to the default backup profile instead
			Factory::getApplication()->getSession()->set('akeebabackup.profile', 1);

			$this->getProfileIdAndName();
		}
	}
}