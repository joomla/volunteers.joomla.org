<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Util;

use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use ReturnTypeWillChange;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected array $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param   array  $items
	 */
	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	/**
	 * Create a new collection instance if the value isn't one already.
	 *
	 * @param   mixed  $items
	 *
	 * @return  static
	 */
	public static function make($items): self
	{
		if (is_null($items))
		{
			return new static;
		}

		if ($items instanceof Collection)
		{
			return $items;
		}

		return new static(is_array($items) ? $items : [$items]);
	}

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function all(): array
	{
		return $this->items;
	}

	/**
	 * Collapse the collection items into a single array.
	 *
	 * @return static
	 */
	public function collapse(): self
	{
		$results = [];

		foreach ($this->items as $values)
		{
			$results = array_merge($results, $values);
		}

		return new static($results);
	}

	/**
	 * Diff the collection with the given items.
	 *
	 * @param   static|array  $items
	 *
	 * @return static
	 */
	public function diff($items): Collection
	{
		return new static(array_diff($this->items, $this->getArrayableItems($items)));
	}

	/**
	 * Execute a callback over each item.
	 *
	 * @param   callable  $callback
	 *
	 * @return static
	 */
	public function each(callable $callback): self
	{
		array_map($callback, $this->items);

		return $this;
	}

	/**
	 * Fetch a nested element of the collection.
	 *
	 * @param   string  $key
	 *
	 * @return static
	 */
	public function fetch(string $key): self
	{
		return new static($this->array_fetch($this->items, $key));
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param   callable  $callback
	 *
	 * @return static
	 */
	public function filter(callable $callback): self
	{
		return new static(array_filter($this->items, $callback));
	}

	/**
	 * Get the first item from the collection.
	 *
	 * @param   callable|null  $callback
	 * @param   mixed          $default
	 *
	 * @return mixed|null
	 */
	public function first(?callable $callback = null, $default = null)
	{
		if (is_null($callback))
		{
			return count($this->items) > 0 ? reset($this->items) : null;
		}
		else
		{
			return $this->array_first($this->items, $callback, $default);
		}
	}

	/**
	 * Get a flattened array of the items in the collection.
	 *
	 * @return static
	 */
	public function flatten(): self
	{
		return new static($this->array_flatten($this->items));
	}

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param   mixed  $key
	 *
	 * @return void
	 */
	public function forget($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Get an item from the collection by key.
	 *
	 * @param   mixed  $key
	 * @param   mixed  $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if (array_key_exists($key, $this->items))
		{
			return $this->items[$key];
		}

		return $this->collapseValue($default);
	}

	/**
	 * Group an associative array by a field or callable value.
	 *
	 * @param   callable|string  $groupBy
	 *
	 * @return static
	 */
	public function groupBy($groupBy): self
	{
		$results = [];

		foreach ($this->items as $key => $value)
		{
			$key = is_callable($groupBy) ? $groupBy($value, $key) : $this->array_get($value, $groupBy);

			$results[$key][] = $value;
		}

		return new static($results);
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param   mixed  $key
	 *
	 * @return bool
	 */
	public function has($key): bool
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Concatenate values of a given key as a string.
	 *
	 * @param   string       $value
	 * @param   string|null  $glue
	 *
	 * @return string
	 */
	public function implode(string $value, ?string $glue = null): string
	{
		if (is_null($glue))
		{
			return implode($this->lists($value));
		}

		return implode($glue, $this->lists($value));
	}

	/**
	 * Intersect the collection with the given items.
	 *
	 * @param   Collection|array  $items
	 *
	 * @return static
	 */
	public function intersect($items): self
	{
		return new static(array_intersect($this->items, $this->getArrayableItems($items)));
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	/**
	 * Get the last item from the collection.
	 *
	 * @return mixed|null
	 */
	public function last()
	{
		return count($this->items) > 0 ? end($this->items) : null;
	}

	/**
	 * Get an array with the values of a given key.
	 *
	 * @param   string       $value
	 * @param   string|null  $key
	 *
	 * @return array
	 */
	public function lists(string $value, ?string $key = null): array
	{
		return $this->array_pluck($this->items, $value, $key);
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @param   callable  $callback
	 *
	 * @return static
	 */
	public function map(callable $callback): Collection
	{
		return new static(array_map($callback, $this->items, array_keys($this->items)));
	}

	/**
	 * Run a map over each of the items, preserving the keys.
	 *
	 * @param   callable  $callback
	 *
	 * @return static
	 */
	public function mapPreserve(callable $callback): Collection
	{
		return new static(array_map($callback, $this->items));
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @param   Collection|array  $items
	 *
	 * @return static
	 */
	public function merge($items): Collection
	{
		return new static(array_merge($this->items, $this->getArrayableItems($items)));
	}

	/**
	 * Get and remove the last item from the collection.
	 *
	 * @return mixed|null
	 */
	public function pop()
	{
		return array_pop($this->items);
	}

	/**
	 * Push an item onto the beginning of the collection.
	 *
	 * @param   mixed  $value
	 *
	 * @return void
	 */
	public function prepend($value)
	{
		array_unshift($this->items, $value);
	}

	/**
	 * Push an item onto the end of the collection.
	 *
	 * @param   mixed  $value
	 *
	 * @return void
	 */
	public function push($value)
	{
		$this->items[] = $value;
	}

	/**
	 * Put an item in the collection by key.
	 *
	 * @param   mixed  $key
	 * @param   mixed  $value
	 *
	 * @return void
	 */
	public function put($key, $value)
	{
		$this->items[$key] = $value;
	}

	/**
	 * Reduce the collection to a single value.
	 *
	 * @param   callable  $callback
	 * @param   mixed     $initial
	 *
	 * @return  mixed
	 */
	public function reduce(callable $callback, $initial = null)
	{
		return array_reduce($this->items, $callback, $initial);
	}

	/**
	 * Get one or more items randomly from the collection.
	 *
	 * @param   int  $amount
	 *
	 * @return mixed
	 */
	public function random(int $amount = 1)
	{
		$keys = array_rand($this->items, $amount);

		return is_array($keys) ? array_intersect_key($this->items, array_flip($keys)) : $this->items[$keys];
	}

	/**
	 * Reverse items order.
	 *
	 * @return static
	 */
	public function reverse(): Collection
	{
		return new static(array_reverse($this->items));
	}

	/**
	 * Get and remove the first item from the collection.
	 *
	 * @return mixed|null
	 */
	public function shift()
	{
		return array_shift($this->items);
	}

	/**
	 * Slice the underlying collection array.
	 *
	 * @param   int       $offset
	 * @param   int|null  $length
	 * @param   bool      $preserveKeys
	 *
	 * @return static
	 */
	public function slice(int $offset, ?int $length = null, bool $preserveKeys = false): Collection
	{
		return new static(array_slice($this->items, $offset, $length, $preserveKeys));
	}

	/**
	 * Sort through each item with a callback.
	 *
	 * @param   callable  $callback
	 *
	 * @return static
	 */
	public function sort(callable $callback): self
	{
		uasort($this->items, $callback);

		return $this;
	}

	/**
	 * Sort the collection using the given callable.
	 *
	 * @param   callable|string  $callback
	 * @param   int              $options
	 * @param   bool             $descending
	 *
	 * @return static
	 */
	public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false): self
	{
		$results = [];

		if (is_string($callback))
		{
			$callback =
				$this->valueRetriever($callback);
		}

		// First we will loop through the items and get the comparator from a callback
		// function which we were given. Then, we will sort the returned values and
		// and grab the corresponding values for the sorted keys from this array.
		foreach ($this->items as $key => $value)
		{
			$results[$key] = $callback($value);
		}

		$descending ? arsort($results, $options)
			: asort($results, $options);

		// Once we have sorted all of the keys in the array, we will loop through them
		// and grab the corresponding model so we can set the underlying items list
		// to the sorted version. Then we'll just return the collection instance.
		foreach (array_keys($results) as $key)
		{
			$results[$key] = $this->items[$key];
		}

		$this->items = $results;

		return $this;
	}

	/**
	 * Sort the collection in descending order using the given callable.
	 *
	 * @param   callable|string  $callback
	 * @param   int              $options
	 *
	 * @return static
	 */
	public function sortByDesc($callback, int $options = SORT_REGULAR): Collection
	{
		return $this->sortBy($callback, $options, true);
	}

	/**
	 * Splice portion of the underlying collection array.
	 *
	 * @param   int    $offset
	 * @param   int    $length
	 * @param   mixed  $replacement
	 *
	 * @return static
	 */
	public function splice(int $offset, int $length = 0, $replacement = []): self
	{
		return new static(array_splice($this->items, $offset, $length, $replacement));
	}

	/**
	 * Get the sum of the given values.
	 *
	 * @param   callable|string  $callback
	 *
	 * @return mixed
	 */
	public function sum($callback)
	{
		if (is_string($callback))
		{
			$callback = $this->valueRetriever($callback);
		}

		return $this->reduce(
			function ($result, $item) use ($callback) {
				return $result += $callback($item);

			}, 0
		);
	}

	/**
	 * Take the first or last {$limit} items.
	 *
	 * @param   int|null  $limit
	 *
	 * @return static
	 */
	public function take(?int $limit = null): self
	{
		if ($limit < 0)
		{
			return $this->slice($limit, abs($limit));
		}

		return $this->slice(0, $limit);
	}

	/**
	 * Transform each item in the collection using a callback.
	 *
	 * @param   callable  $callback
	 *
	 * @return static
	 */
	public function transform(callable $callback): self
	{
		$this->items = array_map($callback, $this->items);

		return $this;
	}

	/**
	 * Return only unique items from the collection array.
	 *
	 * @return static
	 */
	public function unique(): self
	{
		return new static(array_unique($this->items));
	}

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return static
	 */
	public function values(): self
	{
		$this->items = array_values($this->items);

		return $this;
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return array_map(
			function ($value) {
				return (is_object($value) && method_exists($value, 'toArray')) ? $value->toArray() : $value;

			}, $this->items
		);
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Get the collection of items as JSON.
	 *
	 * @param   int  $options
	 *
	 * @return string
	 */
	public function toJson(int $options = 0): string
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return ArrayIterator
	 */
	#[ReturnTypeWillChange]
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Get a CachingIterator instance.
	 *
	 * @param   integer  $flags  Caching iterator flags
	 *
	 * @return CachingIterator
	 */
	public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator
	{
		return new CachingIterator($this->getIterator(), $flags);
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	#[ReturnTypeWillChange]
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param   mixed  $key
	 *
	 * @return bool
	 */
	#[ReturnTypeWillChange]
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param   mixed  $key
	 *
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function offsetGet($key)
	{
		return $this->items[$key];
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param   mixed  $key
	 * @param   mixed  $value
	 *
	 * @return void
	 */
	#[ReturnTypeWillChange]
	public function offsetSet($key, $value)
	{
		if (is_null($key))
		{
			$this->items[] = $value;
		}
		else
		{
			$this->items[$key] = $value;
		}
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param   string  $key
	 *
	 * @return void
	 */
	#[ReturnTypeWillChange]
	public function offsetUnset($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	#[ReturnTypeWillChange]
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * Get a value retrieving callback.
	 *
	 * @param   string  $value
	 *
	 * @return callable
	 */
	protected function valueRetriever(string $value): callable
	{
		return function ($item) use ($value) {
			return is_object($item) ? $item->{$value} : $this->array_get($item, $value);
		};
	}

	/**
	 * Fetch a flattened array of a nested array element.
	 *
	 * @param   array   $array
	 * @param   string  $key
	 *
	 * @return array
	 */
	private function array_fetch(array $array, string $key): array
	{
		foreach (explode('.', $key) as $segment)
		{
			$results = [];

			foreach ($array as $value)
			{
				$value = (array) $value;

				$results[] = $value[$segment];
			}

			$array = array_values($results);
		}

		return array_values($results);
	}

	/**
	 * Return the first element in an array passing a given truth test.
	 *
	 * @param   array     $array
	 * @param   callable  $callback
	 * @param   mixed     $default
	 *
	 * @return mixed
	 */
	private function array_first(array $array, callable $callback, $default = null)
	{
		foreach ($array as $key => $value)
		{
			if (call_user_func($callback, $key, $value))
			{
				return $value;
			}
		}

		return $this->collapseValue($default);
	}

	/**
	 * Return the default value of the given value.
	 *
	 * @param   mixed  $value
	 *
	 * @return mixed
	 */
	private function collapseValue($value)
	{
		return is_callable($value) ? $value() : $value;
	}

	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param   array  $array
	 *
	 * @return array
	 */
	private function array_flatten(array $array): array
	{
		$return = [];

		array_walk_recursive(
			$array, function ($x) use (&$return) {
			$return[] = $x;
		}
		);

		return $return;
	}

	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param   array   $array
	 * @param   string  $key
	 * @param   mixed   $default
	 *
	 * @return mixed
	 */
	private function array_get(array $array, string $key, $default = null)
	{
		if (is_null($key))
		{
			return $array;
		}

		if (isset($array[$key]))
		{
			return $array[$key];
		}

		foreach (explode('.', $key) as $segment)
		{
			if (!is_array($array) || !array_key_exists($segment, $array))
			{
				return $this->collapseValue($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}

	/**
	 * Pluck an array of values from an array.
	 *
	 * @param   array        $array
	 * @param   string       $value
	 * @param   string|null  $key
	 *
	 * @return array
	 */
	private function array_pluck(array $array, string $value, ?string $key = null): array
	{
		$results = [];

		foreach ($array as $item)
		{
			$itemValue = is_object($item) ? $item->{$value} : $item[$value];

			// If the key is "null", we will just append the value to the array and keep
			// looping. Otherwise we will key the array using the value of the key we
			// received from the developer. Then we'll return the final array form.
			if (is_null($key))
			{
				$results[] = $itemValue;
			}
			else
			{
				$itemKey = is_object($item) ? $item->{$key} : $item[$key];

				$results[$itemKey] = $itemValue;
			}
		}

		return $results;
	}

	/**
	 * Results array of items from Collection.
	 *
	 * @param   Collection|array  $items
	 *
	 * @return array
	 */
	private function getArrayableItems($items): array
	{
		if ($items instanceof Collection)
		{
			$items = $items->all();
		}
		elseif (is_object($items) && method_exists($items, 'toArray'))
		{
			$items = $items->toArray();
		}

		return $items;
	}
}