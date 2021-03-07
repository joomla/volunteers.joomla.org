<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Model\Mixin;

defined('_JEXEC') || die;

/**
 * Trait for dealing with data stored as JSON-encoded strings
 */
trait JsonData
{
	/**
	 * Converts the loaded JSON string into an array
	 *
	 * @param   string  $value  The JSON string
	 *
	 * @return  array  The data
	 */
	protected function getAttributeForJson($value)
	{
		if (is_array($value))
		{
			return $value;
		}

		if (empty($value))
		{
			return [];
		}

		$value = json_decode($value, true);

		if (empty($value))
		{
			return [];
		}

		return $value;
	}

	/**
	 * Converts and array into a JSON string
	 *
	 * @param   array|string  $value  The data (or its JSON-encoded form)
	 *
	 * @return  string  The JSON string
	 */
	protected function setAttributeForJson($value)
	{
		if (!is_array($value))
		{
			return $value;
		}

		return json_encode($value);
	}
}
