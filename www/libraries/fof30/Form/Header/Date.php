<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use JHtml;
use JText;

defined('_JEXEC') or die;

/**
 * Generic field header, with text input (search) filter
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Date extends Field
{
	/**
	 * Get the filter field
	 *
	 * @return  string  The HTML
	 */
	protected function getFilter()
	{
		// Initialize some field attributes.
		$format		 = $this->element['format'] ? (string) $this->element['format'] : '%Y-%m-%d';
		$attributes  = array();

		if ($this->element['size'])
		{
			$attributes['size'] = (int) $this->element['size'];
		}

		if ($this->element['maxlength'])
		{
			$attributes['maxlength'] = (int) $this->element['maxlength'];
		}

		if ($this->element['filterclass'])
		{
			$attributes['class'] = (string) $this->element['filterclass'];
		}

		if ((string) $this->element['readonly'] == 'true')
		{
			$attributes['readonly'] = 'readonly';
		}

		if ((string) $this->element['disabled'] == 'true')
		{
			$attributes['disabled'] = 'disabled';
		}

		if ($this->element['onchange'])
		{
			$attributes['onChange'] = (string) $this->element['onchange'];
		}
		else
		{
			$attributes['onChange'] = 'document.adminForm.submit()';
		}

		if ((string) $this->element['placeholder'])
		{
			$attributes['placeholder'] = JText::_((string) $this->element['placeholder']);
		}

		$name = $this->element['searchfieldname'] ? $this->element['searchfieldname'] : $this->name;

		if ($this->element['searchfieldname'])
		{
			$model       = $this->form->getModel();
			$searchvalue = $model->getState((string) $this->element['searchfieldname']);
		}
		else
		{
			$searchvalue = $this->value;
		}

		// Get some system objects.
		$config = $this->form->getContainer()->platform->getConfig();
		$user   = $this->form->getContainer()->platform->getUser();

		// If a known filter is given use it.
		switch (strtoupper((string) $this->element['filter']))
		{
			case 'SERVER_UTC':
				// Convert a date to UTC based on the server timezone.
				if ((int) $this->value)
				{
					// Get a date object based on the correct timezone.
					$date = $this->form->getContainer()->platform->getDate($searchvalue, 'UTC');
					$date->setTimezone(new \DateTimeZone($config->get('offset')));

					// Transform the date string.
					$searchvalue = $date->format('Y-m-d H:i:s', true, false);
				}
				break;

			case 'USER_UTC':
				// Convert a date to UTC based on the user timezone.
				if ((int) $searchvalue)
				{
					// Get a date object based on the correct timezone.
					$date = $this->form->getContainer()->platform->getDate($this->value, 'UTC');
					$date->setTimezone(new \DateTimeZone($user->getParam('timezone', $config->get('offset'))));

					// Transform the date string.
					$searchvalue = $date->format('Y-m-d H:i:s', true, false);
				}
				break;
		}

		return JHtml::_('calendar', $searchvalue, $name, $name, $format, $attributes);
	}

	/**
	 * Get the buttons HTML code
	 *
	 * @return  string  The HTML
	 */
	protected function getButtons()
	{
		return '';
	}
}
