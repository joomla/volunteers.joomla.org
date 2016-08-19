<?php
/**
 * @Copyright
 * @package     Field - Kubik-Rubik Title
 * @author      Viktor Vogel <admin@kubik-rubik.de>
 * @version     Joomla! 3 - 3.2.0 - 2016-03-15
 * @link        https://joomla-extensions.kubik-rubik.de
 *
 * @license     GNU/GPL
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('JPATH_PLATFORM') or die;

class JFormFieldKRTitle extends JFormField
{
	protected $type = 'krtitle';

	protected function getInput()
	{
		return '';
	}

	protected function getLabel()
	{
		// Use static variable to execute the CSS instruction only once
		static $execute_once = false;

		if(empty($execute_once))
		{
			$document = JFactory::getDocument();

			// Set label instruction only for option tabs
			$fieldsets = $this->form->getFieldsets();

			foreach($fieldsets as $fieldset)
			{
				$document->addStyleDeclaration('div#attrib-'.$fieldset->name.' label, div#'.$fieldset->name.' label {width: 20em;}');
			}

			$execute_once = true;
		}

		$label = '<div class="clr"></div>';

		if($this->element['label'])
		{
			$label .= '<div style="padding: 5px 5px 5px 0; font-size: 16px;"><strong>'.JText::_($this->element['label']).'</strong></div>';
		}
		else
		{
			$label .= parent::getLabel();
		}

		if($this->element['description'])
		{
			$label .= '<div style="padding: 5px 5px 5px 0; font-size: 14px;">'.JText::_($this->element['description']).'</div>';
		}

		return $label;
	}
}
