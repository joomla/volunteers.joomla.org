<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Utils;

defined('_JEXEC') || die;

/**
 * ArrayHelper is an array utility class for doing all sorts of odds and ends with arrays.
 *
 * Copied from Joomla Framework to avoid class name issues between Joomla! versions 3 and 4. sortObjects is not included
 * because it needs the UTF-8 package. If you need to use that then you should be using the Joomla! Framework's helper
 * anyway.
 */
final class ArrayHelper
{
	/**
	 * Private constructor to prevent instantiation of this class
	 *
	 * @since   1.0
	 */
	private function __construct()
	{
	}

	/**
	 * Function to convert array to integer values
	 *
	 * @param   array           $array    The source array to convert
	 * @param   int|array|null  $default  A default value (int|array) to assign if $array is not an array
	 *
	 * @return  int[]
	 *
	 * @since   1.0
	 */
	public static function toInteger(array $array, $default = null): array
	{
		if (is_array($array))
		{
			return array_map('intval', $array);
		}

		if ($default === null)
		{
			return [];
		}

		if (is_array($default))
		{
			return static::toInteger($default, null);
		}

		return [(int) $default];
	}

	/**
	 * Utility function to map an array to a stdClass object.
	 *
	 * @param   array    $array      The array to map.
	 * @param   string   $class      Name of the class to create
	 * @param   boolean  $recursive  Convert also any array inside the main array
	 *
	 * @return  object
	 *
	 * @since   1.0
	 */
	public static function toObject(array $array, string $class = 'stdClass', bool $recursive = true)
	{
		$obj = new $class;

		foreach ($array as $k => $v)
		{
			$obj->$k = ($recursive && is_array($v)) ? static::toObject($v, $class) : $v;
		}

		return $obj;
	}

	/**
	 * Utility function to map an array to a string.
	 *
	 * @param   array    $array         The array to map.
	 * @param   string   $inner_glue    The glue (optional, defaults to '=') between the key and the value.
	 * @param   string   $outer_glue    The glue (optional, defaults to ' ') between array elements.
	 * @param   boolean  $keepOuterKey  True if final key should be kept.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function toString(array $array, string $inner_glue = '=', string $outer_glue = ' ', bool $keepOuterKey = false): string
	{
		$output = [];

		foreach ($array as $key => $item)
		{
			if (is_array($item))
			{
				if ($keepOuterKey)
				{
					$output[] = $key;
				}

				// This is value is an array, go and do it again!
				$output[] = static::toString($item, $inner_glue, $outer_glue, $keepOuterKey);
			}
			else
			{
				$output[] = $key . $inner_glue . '"' . $item . '"';
			}
		}

		return implode($outer_glue, $output);
	}

	/**
	 * Utility function to map an object to an array
	 *
	 * @param   object       $p_obj    The source object
	 * @param   boolean      $recurse  True to recurse through multi-level objects
	 * @param   string|null  $regex    An optional regular expression to match on field names
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function fromObject($p_obj, bool $recurse = true, ?string $regex = null): array
	{
		if (is_object($p_obj) || is_array($p_obj))
		{
			return self::arrayFromObject($p_obj, $recurse, $regex);
		}

		return [];
	}

	/**
	 * Extracts a column from an array of arrays or objects
	 *
	 * @param   array        $array     The source array
	 * @param   string       $valueCol  The index of the column or name of object property to be used as value
	 *                                  It may also be NULL to return complete arrays or objects (this is
	 *                                  useful together with <var>$keyCol</var> to reindex the array).
	 * @param   string|null  $keyCol    The index of the column or name of object property to be used as key
	 *
	 * @return  array  Column of values from the source array
	 *
	 * @since   1.0
	 * @see     http://php.net/manual/en/language.types.array.php
	 * @see     http://php.net/manual/en/function.array-column.php
	 */
	public static function getColumn(array $array, string $valueCol, ?string $keyCol = null): array
	{
		$result = [];

		foreach ($array as $item)
		{
			// Convert object to array
			$subject = is_object($item) ? static::fromObject($item) : $item;

			/*
			 * We process arrays (and objects already converted to array)
			 * Only if the value column (if required) exists in this item
			 */
			if (is_array($subject) && (!isset($valueCol) || isset($subject[$valueCol])))
			{
				// Use whole $item if valueCol is null, else use the value column.
				$value = isset($valueCol) ? $subject[$valueCol] : $item;

				// Array keys can only be integer or string. Casting will occur as per the PHP Manual.
				if (isset($keyCol) && isset($subject[$keyCol]) && is_scalar($subject[$keyCol]))
				{
					$key          = $subject[$keyCol];
					$result[$key] = $value;
				}
				else
				{
					$result[] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * Utility function to return a value from a named array or a specified default
	 *
	 * @param   array|\ArrayAccess  $array    A named array or object that implements ArrayAccess
	 * @param   string              $name     The key to search for
	 * @param   mixed               $default  The default value to give if no key found
	 * @param   string              $type     Return type for the variable (INT, FLOAT, STRING, WORD, BOOLEAN, ARRAY)
	 *
	 * @return  mixed
	 *
	 * @throws  \InvalidArgumentException
	 * @since   1.0
	 */
	public static function getValue(array $array, string $name, $default = null, string $type = '')
	{
		if (!is_array($array) && !($array instanceof \ArrayAccess))
		{
			throw new \InvalidArgumentException('The object must be an array or an object that implements ArrayAccess');
		}

		$result = null;

		if (isset($array[$name]))
		{
			$result = $array[$name];
		}

		// Handle the default case
		if (is_null($result))
		{
			$result = $default;
		}

		// Handle the type constraint
		switch (strtoupper($type))
		{
			case 'INT':
			case 'INTEGER':
				// Only use the first integer value
				@preg_match('/-?\d+/', $result, $matches);
				$result = @(int) $matches[0];
				break;

			case 'FLOAT':
			case 'DOUBLE':
				// Only use the first floating point value
				@preg_match('/-?\d+(\.\d+)?/', $result, $matches);
				$result = @(float) $matches[0];
				break;

			case 'BOOL':
			case 'BOOLEAN':
				$result = (bool) $result;
				break;

			case 'ARRAY':
				if (!is_array($result))
				{
					$result = [$result];
				}
				break;

			case 'STRING':
				$result = (string) $result;
				break;

			case 'WORD':
				$result = (string) preg_replace('#\W#', '', $result);
				break;

			case 'NONE':
			default:
				// No casting necessary
				break;
		}

		return $result;
	}

	/**
	 * Takes an associative array of arrays and inverts the array keys to values using the array values as keys.
	 *
	 * Example:
	 * $input = array(
	 *     'New' => array('1000', '1500', '1750'),
	 *     'Used' => array('3000', '4000', '5000', '6000')
	 * );
	 * $output = ArrayHelper::invert($input);
	 *
	 * Output would be equal to:
	 * $output = array(
	 *     '1000' => 'New',
	 *     '1500' => 'New',
	 *     '1750' => 'New',
	 *     '3000' => 'Used',
	 *     '4000' => 'Used',
	 *     '5000' => 'Used',
	 *     '6000' => 'Used'
	 * );
	 *
	 * @param   array  $array  The source array.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function invert(array $array): array
	{
		$return = [];

		foreach ($array as $base => $values)
		{
			if (!is_array($values))
			{
				continue;
			}

			foreach ($values as $key)
			{
				// If the key isn't scalar then ignore it.
				if (is_scalar($key))
				{
					$return[$key] = $base;
				}
			}
		}

		return $return;
	}

	/**
	 * Method to determine if an array is an associative array.
	 *
	 * @param   array  $array  An array to test.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public static function isAssociative(array $array): bool
	{
		if (is_array($array))
		{
			foreach (array_keys($array) as $k => $v)
			{
				if ($k !== $v)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Pivots an array to create a reverse lookup of an array of scalars, arrays or objects.
	 *
	 * @param   array        $source  The source array.
	 * @param   string|null  $key     Where the elements of the source array are objects or arrays, the key to pivot
	 *                                on.
	 *
	 * @return  array  An array of arrays pivoted either on the value of the keys, or an individual key of an object or
	 *                 array.
	 *
	 * @since   1.0
	 */
	public static function pivot(array $source, ?string $key = null): array
	{
		$result  = [];
		$counter = [];

		foreach ($source as $index => $value)
		{
			// Determine the name of the pivot key, and its value.
			if (is_array($value))
			{
				// If the key does not exist, ignore it.
				if (!isset($value[$key]))
				{
					continue;
				}

				$resultKey   = $value[$key];
				$resultValue = $source[$index];
			}
			elseif (is_object($value))
			{
				// If the key does not exist, ignore it.
				if (!isset($value->$key))
				{
					continue;
				}

				$resultKey   = $value->$key;
				$resultValue = $source[$index];
			}
			else
			{
				// Just a scalar value.
				$resultKey   = $value;
				$resultValue = $index;
			}

			// The counter tracks how many times a key has been used.
			if (empty($counter[$resultKey]))
			{
				// The first time around we just assign the value to the key.
				$result[$resultKey]  = $resultValue;
				$counter[$resultKey] = 1;
			}
			elseif ($counter[$resultKey] == 1)
			{
				// If there is a second time, we convert the value into an array.
				$result[$resultKey] = [
					$result[$resultKey],
					$resultValue,
				];
				$counter[$resultKey]++;
			}
			else
			{
				// After the second time, no need to track any more. Just append to the existing array.
				$result[$resultKey][] = $resultValue;
			}
		}

		unset($counter);

		return $result;
	}

	/**
	 * Multidimensional-array-safe unique test
	 *
	 * @param   array  $array  The array to make unique.
	 *
	 * @return  array
	 *
	 * @see     http://php.net/manual/en/function.array-unique.php
	 * @since   1.0
	 */
	public static function arrayUnique(array $array): array
	{
		$array = array_map('serialize', $array);
		$array = array_unique($array);

		return array_map('unserialize', $array);
	}

	/**
	 * An improved array_search that allows for partial matching of strings values in associative arrays.
	 *
	 * @param   string   $needle         The text to search for within the array.
	 * @param   array    $haystack       Associative array to search in to find $needle.
	 * @param   boolean  $caseSensitive  True to search case sensitive, false otherwise.
	 *
	 * @return  mixed    Returns the matching array $key if found, otherwise false.
	 *
	 * @since   1.0
	 */
	public static function arraySearch(string $needle, array $haystack, bool $caseSensitive = true)
	{
		foreach ($haystack as $key => $value)
		{
			$searchFunc = ($caseSensitive) ? 'strpos' : 'stripos';

			if ($searchFunc($value, $needle) === 0)
			{
				return $key;
			}
		}

		return false;
	}

	/**
	 * Method to recursively convert data to a one dimension array.
	 *
	 * @param   array|object  $array      The array or object to convert.
	 * @param   string        $separator  The key separator.
	 * @param   string        $prefix     Last level key prefix.
	 *
	 * @return  array
	 *
	 * @since   1.3.0
	 */
	public static function flatten($array, string $separator = '.', string $prefix = ''): array
	{
		if ($array instanceof \Traversable)
		{
			$array = iterator_to_array($array);
		}
		elseif (is_object($array))
		{
			$array = get_object_vars($array);
		}

		foreach ($array as $k => $v)
		{
			$key = $prefix ? ($prefix . $separator . $k) : $k;

			if (is_object($v) || is_array($v))
			{
				$array = array_merge($array, static::flatten($v, $separator, $key));
			}
			else
			{
				$array[$key] = $v;
			}
		}

		return $array;
	}

	/**
	 * Utility function to map an object or array to an array
	 *
	 * @param   mixed    $item     The source object or array
	 * @param   boolean  $recurse  True to recurse through multi-level objects
	 * @param   string   $regex    An optional regular expression to match on field names
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private static function arrayFromObject($item, bool $recurse, ?string $regex): array
	{
		if (is_object($item))
		{
			$result = [];

			foreach (get_object_vars($item) as $k => $v)
			{
				if (!$regex || preg_match($regex, $k))
				{
					$result[$k] = $recurse ? self::arrayFromObject($v, $recurse, $regex) : $v;
				}
			}

			return $result;
		}

		if (is_array($item))
		{
			$result = [];

			foreach ($item as $k => $v)
			{
				$result[$k] = self::arrayFromObject($v, $recurse, $regex);
			}

			return $result;
		}

		return $item;
	}
}
