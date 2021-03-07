<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace  FOF40\Model\DataModel\Filter;

defined('_JEXEC') || die;

class Boolean extends Number
{
	/**
	 * Is it a null or otherwise empty value?
	 *
	 * @param   mixed  $value  The value to test for emptiness
	 *
	 * @return  bool
	 */
	public function isEmpty($value)
	{
		return is_null($value) || ($value === '');
	}
} 
