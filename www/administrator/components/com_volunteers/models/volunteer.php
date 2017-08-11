<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Team model.
 */
class VolunteersModelVolunteer extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 */
	public $typeAlias = 'com_volunteers.volunteer';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 */
	protected $text_prefix = 'COM_VOLUNTEERS';

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string $type   The table name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 */
	public function getTable($type = 'Volunteer', $prefix = 'VolunteersTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_volunteers.volunteer', 'volunteer', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_volunteers.edit.volunteer.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_volunteers.volunteer', $data);

		return $data;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable $table A reference to a JTable object.
	 *
	 * @return  void
	 */
	protected function prepareTable($table)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		if (empty($table->id))
		{
			// Set the values

			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('#__volunteers_volunteers'));

				$db->setQuery($query);
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
			else
			{
				// Set the values
				$table->modified    = $date->toSql();
				$table->modified_by = $user->id;
			}
		}

		// Increment the version number.
		$table->version++;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 */
	public function save($data)
	{
		$app = JFactory::getApplication();

		// Joomla User
		$dataUser = array(
			'name'      => $data['name'],
			'username'  => JStringPunycode::emailToPunycode($data['email']),
			'password'  => (isset($data['password1'])) ? $data['password1'] : '',
			'password2' => (isset($data['password2'])) ? $data['password2'] : '',
			'email'     => JStringPunycode::emailToPunycode($data['email'])
		);

		// Handle com_users changes
		if (isset($data['id']))
		{
			$userId         = (int) $this->getItem($data['id'])->user_id;
			$dataUser['id'] = $userId;
			$user           = new JUser($userId);
		}
		else
		{
			$user                 = new JUser;
			$params               = JComponentHelper::getParams('com_users');
			$dataUser['groups'][] = $params->get('new_usertype', 2);
		}

		// Bind the data.
		if (!$user->bind($dataUser))
		{
			throw new Exception("Could not bind data. Error: " . $user->getError());
		}

		// Store the data.
		if (!$user->save())
		{
			throw new Exception("Could not save user. Error: " . $user->getError());
		}

		// Get User ID
		$data['user_id'] = $user->id;

		// Unset data
		unset($data['email']);
		unset($data['password1']);
		unset($data['password2']);

		// Set alias
		$data['alias'] = JApplicationHelper::stringURLSafe($data['name']);

		$return = parent::save($data);

		// Store the newly created volunteer ID
		$volunteerId = $this->getState('volunteer.id');
		$app->setUserState('com_volunteers.registration.id', $volunteerId);

		return $return;
	}

	/**
	 * Method to get team data.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function &getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		$item = new JObject;

		if ($pk > 0)
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select($this->getState('item.select', 'a.*, user.name AS name'))
					->from('#__volunteers_volunteers AS a')
					->where('a.id = ' . (int) $pk);

				// Join on user table.
				$query->select('user.email AS email')
					->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' on user.id = a.user_id');

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived  = $this->getState('filter.archived');

				if (is_numeric($published))
				{
					$query->where('(a.published = ' . (int) $published . ' OR a.published =' . (int) $archived . ')')
						->where('(c.published = ' . (int) $published . ' OR c.published =' . (int) $archived . ')');
				}

				$db->setQuery($query);

				$data = $db->loadObject();

				if (empty($data))
				{
					JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_VOLUNTEER_NOT_FOUND'));
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived)))
				{
					JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_VOLUNTEER_NOT_FOUND'));
				}

				// Make sure we have http:// or https://
				if($data->website)
				{
					$data->website = parse_url($data->website, PHP_URL_SCHEME) == '' ? 'http://' . $data->website : $data->website;
				}

				return $data;
			}
			catch (Exception $e)
			{
				$this->setError($e);

				return false;
			}
		}
		else
		{
			JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_VOLUNTEER_NOT_FOUND'));
		}

		// Convert to the JObject before adding other data.
		$properties = $this->getTable()->getProperties(1);
		$item       = ArrayHelper::toObject($properties, 'JObject');

		return $item;
	}

	public function getVolunteerId($userId = null)
	{
		if (empty($userId))
		{
			return false;
		}

		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('id')
			->from('#__volunteers_volunteers')
			->where($db->quoteName('user_id') . ' = ' . (int) $userId);

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Method to get Department Members.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getVolunteerTeams($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		// Get members
		$model = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.volunteer', $pk);
		$items = $model->getItems();

		$teams               = new stdClass();
		$teams->active       = array();
		$teams->honorroll    = array();
		$teams->activemember = false;

		// Check for active or inactive members
		foreach ($items as $item)
		{
			if ($item->department && ($item->department_parent_id == 0))
			{
				$item->link = JRoute::_('index.php?option=com_volunteers&view=board&id=' . $item->department);
				$item->name = $item->department_title;
			}
			elseif ($item->department)
			{
				$item->link = JRoute::_('index.php?option=com_volunteers&view=department&id=' . $item->department);
				$item->name = $item->department_title;
			}
			elseif ($item->team)
			{
				$item->link = JRoute::_('index.php?option=com_volunteers&view=team&id=' . $item->team);
				$item->name = $item->team_title;
			}

			if ($item->date_ended == '0000-00-00')
			{
				$teams->active[] = $item;
			}
			else
			{
				$teams->honorroll[] = $item;
			}

			if ($item->date_ended == '0000-00-00' && $item->position != 8)
			{
				$teams->activemember = true;
			}
		}

		return $teams;
	}
}
