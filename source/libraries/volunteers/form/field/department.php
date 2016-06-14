<?php


defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * field type
 *
 * @package  volunteers
 * @since    1.0.0
 */
class VolunteersFormFieldDepartment extends JFormFieldList
{
	/**
	 * The form field type.
	 */
	protected $type = 'Department';

	/**
	 * Method to get the field options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		$options = array();

		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('title as text, volunteers_department_id as value')
			->from('#__volunteers_departments')
			->where('enabled = 1');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseWarning(500, $db->getErrorMsg());
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
