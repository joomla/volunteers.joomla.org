<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

if (class_exists('JFormFieldBackupprofiles'))
{
	return;
}

/**
 * Our main element class, creating a multi-select list out of an SQL statement
 */
class JFormFieldBackupprofiles extends JFormField
{
	/**
	 * Element name
	 *
	 * @var        string
	 */
	protected $name = 'Backupprofiles';

	function getInput()
	{
		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('id'),
				$db->qn('description'),
			))->from($db->qn('#__ak_profiles'));
		$db->setQuery($query);
		$key = 'id';
		$val = 'description';

		$objectList = $db->loadObjectList();

		if (!is_array($objectList))
		{
			$objectList = array();
		}

		foreach ($objectList as $o)
		{
			$o->description = "#{$o->id}: {$o->description}";
		}

		$showNone = $this->element['show_none'] ? (string)$this->element['show_none'] : '';
		$showNone = in_array(strtolower($showNone), array('yes', '1', 'true', 'on'));

		if ($showNone)
		{
			$defaultItem = (object)array(
				'id' => '0',
				'description' => JText::_('COM_AKEEBA_FORMFIELD_BACKUPPROFILES_NONE')
			);

			array_unshift($objectList, $defaultItem);
		}

		return JHtml::_('select.genericlist', $objectList, $this->name, 'class="inputbox"', $key, $val, $this->value, $this->id);
	}
}
