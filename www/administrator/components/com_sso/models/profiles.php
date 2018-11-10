<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * SSO Profiles model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoModelProfiles extends ListModel
{
	/**
	 * Database connector
	 *
	 * @var    JDatabaseDriverMysqli
	 * @since  1.0.0
	 */
	private $db;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'ordering', 'profiles.ordering',
				'id', 'profiles.id',
				'name', 'profiles.name',
				'published', 'profiles.published',
			);
		}

		$this->db = Factory::getDbo();

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('profiles.ordering', 'asc');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 *
	 * @throws  \RuntimeException
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$query = $this->db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'profiles.*'
			)
		);

		$query->from($this->db->quoteName('#__sso_profiles', 'profiles'));

		// If the model is set to check item state, add to the query.
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('profiles.published = ' . (int) $published);
		}

		// Add the list ordering clause.
		$query->order(
			$this->db->quoteName(
				$this->db->escape(
					$this->getState('list.ordering', 'profiles.ordering')
				)
			) . ' ' . $this->db->escape($this->getState('list.direction', 'ASC'))
		);

		return $query;
	}
}
