<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Field;

defined('_JEXEC') || die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

/**
 * Our main element class, creating a multi-select list out of an SQL statement
 */
class BackupprofilesField extends ListField
{
	protected $type = 'Backupprofiles';

	protected function getInput()
	{
		// The default option goes on top
		$showNone = $this->element['show_none'] ? (string) $this->element['show_none'] : '';
		$showNone = in_array(strtolower($showNone), ['yes', '1', 'true', 'on']);

		if ($showNone)
		{
			$this->addOption(Text::_('COM_AKEEBABACKUP_FILTER_SELECT_PROFILEID'), [
				'value' => ''
			]);
		}

		// Add options for each and every backup profile
		/** @var DatabaseDriver $db */
		$db = method_exists($this, 'getDatabase')
			? $this->getDatabase()
			:Factory::getContainer()->get(DatabaseInterface::class);

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
				$db->qn('description'),
			])->from($db->qn('#__akeebabackup_profiles'));
		$db->setQuery($query);

		$objectList = $db->loadObjectList() ?? [];

		foreach ($objectList as $o)
		{
			$this->addOption("#{$o->id}: {$o->description}", [
				'value' => $o->id,
			]);
		}

		// Call the parent method and be done with it
		return parent::getInput();
	}
}
