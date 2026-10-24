<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

trait ViewProfileListTrait
{
	/**
	 * List of backup profiles, for use with \Joomla\CMS\HTML\Helpers\Select
	 *
	 * @var   array
	 */
	public $profileList = [];

	/**
	 * Populates the profileList property with an options list for use by JHtmlSelect
	 *
	 * @param   bool  $includeId  Should I include the profile ID in front of the name?
	 *
	 * @return  void
	 */
	protected function getProfileList($includeId = true)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$access_levels = JoomlaFactory::getApplication()->getIdentity()->getAuthorisedViewLevels();

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
				$db->qn('description'),
			])->from($db->qn('#__akeebabackup_profiles'))
			->whereIn($db->qn('access'), $access_levels)
			->order($db->qn('id') . " ASC");

		$db->setQuery($query);
		$rawList = $db->loadAssocList();

		$this->profileList = [];

		if (!is_array($rawList))
		{
			return;
		}

		foreach ($rawList as $row)
		{
			$description = $row['description'];

			if ($includeId)
			{
				$description = '#' . $row['id'] . '. ' . $description;
			}

			$this->profileList[] = HTMLHelper::_('select.option', $row['id'], $description);
		}
	}
}