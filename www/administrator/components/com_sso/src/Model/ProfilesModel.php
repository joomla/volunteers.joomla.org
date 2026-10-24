<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use RuntimeException;

/**
 * SSO Profiles model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class ProfilesModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'ordering',
				'profiles.ordering',
				'id',
				'profiles.id',
				'name',
				'profiles.name',
				'published',
				'profiles.published',
            ];
		}

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
	protected function populateState($ordering = null, $direction = null): void
	{
		// List state information.
		parent::populateState('profiles.ordering', 'asc');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery|string
	 *
	 * @since   1.0.0
	 *
	 * @throws  RuntimeException
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'profiles.*'
			)
		);

		$query->from($db->quoteName('#__sso_profiles', 'profiles'));

		// If the model is set to check item state, add to the query.
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('profiles.published = ' . (int) $published);
		}

		// Add the list ordering clause.
		$query->order(
			$db->quoteName(
				$db->escape(
					$this->getState('list.ordering', 'profiles.ordering')
				)
			) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
		);

		return $query;
	}
}
