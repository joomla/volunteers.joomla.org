<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Table;

defined('JPATH_PLATFORM') or die;

/**
 * Viewlevels table class.
 *
 * @since  1.7.0
 */
class ViewLevel extends Table
{
	/**
	 * Constructor
	 *
	 * @param   \JDatabaseDriver  $db  Database driver object.
	 *
	 * @since   1.7.0
	 */
	public function __construct($db)
	{
		parent::__construct('#__viewlevels', 'id', $db);
	}

	/**
	 * Method to bind the data.
	 *
	 * @param   array  $array   The data to bind.
	 * @param   mixed  $ignore  An array or space separated list of fields to ignore.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.7.0
	 */
	public function bind($array, $ignore = '')
	{
		// Bind the rules as appropriate.
		if (isset($array['rules']))
		{
			if (is_array($array['rules']))
			{
				$array['rules'] = json_encode($array['rules']);
			}
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to check the current record to save
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.7.0
	 */
	public function check()
	{
		// Validate the title.
		if ((trim($this->title)) == '')
		{
			$this->setError(\JText::_('JLIB_DATABASE_ERROR_VIEWLEVEL'));

			return false;
		}

		// Check for a duplicate title.
		$db = $this->_db;
		$query = $db->getQuery(true)
			->select('COUNT(title)')
			->from($db->quoteName('#__viewlevels'))
			->where($db->quoteName('title') . ' = ' . $db->quote($this->title))
			->where($db->quoteName('id') . ' != ' . (int) $this->id);
		$db->setQuery($query);

		if ($db->loadResult() > 0)
		{
			$this->setError(\JText::sprintf('JLIB_DATABASE_ERROR_USERLEVEL_NAME_EXISTS', $this->title));

			return false;
		}

		return true;
	}
}
