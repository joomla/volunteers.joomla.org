<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use JText;

defined('_JEXEC') or die;

/**
 * Row selection checkbox
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class RowSelect extends Field
{
	/**
	 * Get the header
	 *
	 * @return  string  The header HTML
	 */
	protected function getHeader()
	{
		return '<input type="checkbox" name="checkall-toggle" value="" title="'
			. JText::_('JGLOBAL_CHECK_ALL')
			. '" onclick="Joomla.checkAll(this)" />';
	}
}
