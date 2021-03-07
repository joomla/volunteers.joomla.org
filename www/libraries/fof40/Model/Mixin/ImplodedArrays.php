<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Model\Mixin;

defined('_JEXEC') || die;

/**
 * Trait for dealing with imploded arrays, stored as comma-separated values
 */
trait ImplodedArrays
{
	/**
	 * Converts the loaded comma-separated list into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getAttributeForImplodedArray($value)
	{
		if (is_array($value))
		{
			return $value;
		}

		if (empty($value))
		{
			return [];
		}

		$value = explode(',', $value);

		return array_map('trim', $value);
	}

	/**
	 * Converts an array of values into a comma separated list
	 *
	 * @param   array|string  $value  The array of values (or the already imploded array as a string)
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setAttributeForImplodedArray($value)
	{
		if (!is_array($value))
		{
			return $value;
		}

		$value = array_map('trim', $value);

		return implode(',', $value);
	}
}
