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
 * Generic field header, without any filters
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Field extends HeaderBase
{
	/**
	 * Get the header
	 *
	 * @return  string  The header HTML
	 */
	protected function getHeader()
	{
		$sortable = ($this->element['sortable'] != 'false');

		$label = $this->getLabel();

		if ($sortable)
		{
			$view = $this->form->getView();

			return JHTML::_('grid.sort', $label, $this->name,
				$view->getLists()->order_Dir, $view->getLists()->order,
				$this->form->getModel()->task
			);
		}
		else
		{
			return JText::_($label);
		}
	}
}
