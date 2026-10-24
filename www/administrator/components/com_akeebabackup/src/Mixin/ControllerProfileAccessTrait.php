<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfilesModel;
use Joomla\CMS\Factory as JoomlaFactory;

defined('_JEXEC') or die;

trait ControllerProfileAccessTrait
{
	/**
	 * @param $profile_id
	 *
	 * @return bool
	 */
	protected function checkProfileAccess($profile_id)
	{
		/** @var ProfilesModel $profileModel */
		$profileModel  = $this->getModel('Profiles', 'Administrator', ['ignore_request' => true]);
		$access_levels = JoomlaFactory::getApplication()->getIdentity()->getAuthorisedViewLevels();

		$profileModel->setState('filter.access_level', $access_levels);
		$profileModel->setState('list.start', 0);
		$profileModel->setState('list.limit', 0);
		$profiles = $profileModel->getItems();

		$profileIDs = array_map(function($profile){
			return (int) $profile->id;
		}, $profiles ?: []);

		return !empty($profile_id) && in_array((int)$profile_id, $profileIDs, true);
	}
}