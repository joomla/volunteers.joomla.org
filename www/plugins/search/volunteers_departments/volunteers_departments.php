<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// load volunteers language file
$jlang = JFactory::getLanguage();
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, null, true);

class plgSearchVolunteers_departments extends JPlugin
{
	/**
	 * Determine areas searchable by this plugin.
	 *
	 * @return  array  An array of search areas.
	 */
	public function onContentSearchAreas()
	{
		static $areas = array(
			'departments' => 'COM_VOLUNTEERS_TITLE_DEPARTMENTS'
		);

		return $areas;
	}

	/**
	 * Search content (articles).
	 * The SQL must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav.
	 *
	 * @param   string $text     Target search string.
	 * @param   string $phrase   Matching option (possible values: exact|any|all).  Default is "any".
	 * @param   string $ordering Ordering option (possible values: newest|oldest|popular|alpha|category).  Default is "newest".
	 * @param   mixed  $areas    An array if the search it to be restricted to areas or null to search all areas.
	 *
	 * @return  array  Search results.
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		if (is_array($areas))
		{
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas())))
			{
				return array();
			}
		}

		$text = trim($text);

		if ($text == '')
		{
			return array();
		}

		$db = JFactory::getDbo();

		switch ($phrase)
		{
			case 'exact':
				$text      = $db->quote('%' . $db->escape($text, true) . '%', false);
				$wheres2   = array();
				$wheres2[] = 'title LIKE ' . $text;
				$wheres2[] = 'description LIKE ' . $text;
				$where     = '(' . implode(') OR (', $wheres2) . ')';
				break;

			case 'all':
			case 'any':
			default:
				$words  = explode(' ', $text);
				$wheres = array();

				foreach ($words as $word)
				{
					$word      = $db->quote('%' . $db->escape($word, true) . '%', false);
					$wheres2   = array();
					$wheres2[] = 'LOWER(title) LIKE LOWER(' . $word . ')';
					$wheres2[] = 'LOWER(description) LIKE LOWER(' . $word . ')';
					$wheres[]  = implode(' OR ', $wheres2);
				}

				$where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
		}

		switch ($ordering)
		{
			case 'category':
			case 'popular':
			case 'oldest':
				$order = 'ordering ASC';
				break;

			case 'alpha':
				$order = 'title ASC';
				break;

			case 'newest':
			default:
				$order = 'created DESC';
				break;
		}

		$query = $db->getQuery(true);

		$query
			->select('id, title, created, description AS text, \'' . JText::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS') . '\' AS section, \'1\' AS browsernav')
			->from($db->qn('#__volunteers_departments'))
			->where('(' . $where . ') AND state = 1')
			->order($order);

		$items = $db->setQuery($query)->loadObjectList();

		foreach ($items as $item)
		{
			$item->href = JRoute::_('index.php?option=com_volunteers&view=department&id=' . $item->id);
		}

		return $items;
	}
}
