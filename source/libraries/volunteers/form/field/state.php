<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * field type
 *
 * @package  volunteers
 * @since    2.0.0
 */
class VolunteersFormFieldState extends JFormFieldList
{
	/**
	 * The form field type.
	 */
	protected $type = 'State';

	/**
	 * Method to get the field options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		$options = array();

		$options[] = JHtml::_('select.option', 0, 'In Formation');
		$options[] = JHtml::_('select.option', 1, 'Official');
		$options[] = JHtml::_('select.option', 2, 'Unofficial');

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
