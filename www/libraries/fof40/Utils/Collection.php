<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Utils;

defined('_JEXEC') || die;

use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items = [];

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
	 * Create a new collection instance if the value isn't one already. If given an array then the array is wrapped in
	 * a Collection. Otherwise we return a Collection with only one item, whatever was passed in $items. If you pass
	 * null you get an empty Collection.
	 *
	 * @param   array|mixed  $items
	 *
	 * @return static
	 */
	public static function make($items): Collection
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
	 * Collapse the collection items into a single array. This assumes that the Collection is composed of arrays. We
	 * are essentially merging all of these arrays and creating a new Collection out of them.
	 *
	 * If $this->items = [ ['a','b'], ['c','d'], ['e'] ] after collapse it becomes [ 'a','b','c','d','e' ]
	 *
	 * @return Collection
	 */
	public function collapse(): Collection
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
	 * @param   Collection|array  $items
	 *
	 * @return Collection
	 */
	public function diff($items): Collection
	{
		return new static(array_diff($this->items, $this->getIterableItemsAsArray($items)));
	}

	/**
	 * Execute a callback over each item.
	 *
	 * @param   Closure  $callback
	 *
	 * @return Collection
	 */
	public function each(Closure $callback): Collection
	{
		array_map($callback, $this->items);

		return $this;
	}

	/**
	 * Fetch a nested element of the collection.
	 *
	 * @param   string  $key
	 *
	 * @return Collection
	 */
	public function fetch(string $key): Collection
	{
		return new static(array_fetch($this->items, $key));
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param   Closure  $callback
	 *
	 * @return Collection
	 */
	public function filter(Closure $callback): Collection
	{
		return new static(array_filter($this->items, $callback));
	}

	/**
	 * Get the first item from the collection.
	 *
	 * @param   \Closure  $callback
	 * @param   mixed     $default
	 *
	 * @return mixed|null
	 */
	public function first(Closure $callback = null, $default = null)
	{
		if (is_null($callback))
		{
			return count($this->items) > 0 ? reset($this->items) : null;
		}
		else
		{
			return array_first($this->items, $callback, $default);
		}
	}

	/**
	 * Get a flattened array of the items in the collection.
	 *
	 * @return Collection
	 */
	public function flatten(): Collection
	{
		return new static(array_flatten($this->items));
	}

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param   mixed  $key
	 *
	 * @return void
	 */
	public function forget(string $key): void
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
	public function get(string $key, $default = null)
	{
		if (array_key_exists($key, $this->items))
		{
			return $this->items[$key];
		}

		return value($default);
	}

	/**
	 * Group an associative array by a field or Closure value.
	 *
	 * @param   callable|string  $groupBy
	 *
	 * @return Collection
	 */
	public function groupBy($groupBy): Collection
	{
		$results = [];

		foreach ($this->items as $key => $value)
		{
			$key = is_callable($groupBy) ? $groupBy($value, $key) : array_get($value, $groupBy);

			$results[$key][] = $value;
		}

		return new static($results);
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param   string  $key
	 *
	 * @return bool
	 */
	public function has(string $key): bool
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
	 * @return Collection
	 */
	public function intersect($items): Collection
	{
		return new static(array_intersect($this->items, $this->getIterableItemsAsArray($items)));
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
	public function lists(string $value, ?string $key = null)
	{
		return array_pluck($this->items, $value, $key);
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @param   Closure  $callback
	 *
	 * @return Collection
	 */
	public function map(Closure $callback): Collection
	{
		return new static(array_map($callback, $this->items, array_keys($this->items)));
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @param   Collection|array  $items
	 *
	 * @return Collection
	 */
	public function merge($items): Collection
	{
		return new static(array_merge($this->items, $this->getIterableItemsAsArray($items)));
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
	public function prepend($value): void
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
	public function push($value): void
	{
		$this->items[] = $value;
	}

	/**
	 * Put an item in the collection by key.
	 *
	 * @param   string  $key
	 * @param   mixed   $value
	 *
	 * @return void
	 */
	public function put(string $key, $value): void
	{
		$this->items[$key] = $value;
	}

	/**
	 * Reduce the collection to a single value.
	 *
	 * @param   callable  $callback
	 * @param   mixed     $initial
	 *
	 * @return mixed
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
	 * @return Collection
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
	 * @return Collection
	 */
	public function slice(int $offset, ?int $length = null, bool $preserveKeys = false): Collection
	{
		return new static(array_slice($this->items, $offset, $length, $preserveKeys));
	}

	/**
	 * Sort through each item with a callback.
	 *
	 * @param   Closure  $callback
	 *
	 * @return Collection
	 */
	public function sort(Closure $callback): Collection
	{
		uasort($this->items, $callback);

		return $this;
	}

	/**
	 * Sort the collection using the given Closure.
	 *
	 * @param   \Closure|string  $callback
	 * @param   int              $options
	 * @param   bool             $descending
	 *
	 * @return Collection
	 */
	public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false): Collection
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
	 * Sort the collection in descending order using the given Closure.
	 *
	 * @param   \Closure|string  $callback
	 * @param   int              $options
	 *
	 * @return Collection
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
	 * @return Collection
	 */
	public function splice(int $offset, int $length = 0, $replacement = []): Collection
	{
		return new static(array_splice($this->items, $offset, $length, $replacement));
	}

	/**
	 * Get the sum of the given values.
	 *
	 * @param   \Closure|string  $callback
	 *
	 * @return mixed
	 */
	public function sum($callback)
	{
		if (is_string($callback))
		{
			$callback = $this->valueRetriever($callback);
		}

		return $this->reduce(function ($result, $item) use ($callback) {
			return $result += $callback($item);

		}, 0);
	}

	/**
	 * Take the first or last {$limit} items.
	 *
	 * @param   int|null  $limit
	 *
	 * @return Collection
	 */
	public function take(?int $limit = null): Collection
	{
		if ($limit < 0)
		{
			return $this->slice($limit, abs($limit));
		}

		return $this->slice(0, $limit);
	}

	/**
	 * Resets the Collection (removes all items)
	 *
	 * @return  Collection
	 */
	public function reset(): Collection
	{
		$this->items = [];

		return $this;
	}

	/**
	 * Transform each item in the collection using a callback.
	 *
	 * @param   callable  $callback
	 *
	 * @return Collection
	 */
	public function transform(callable $callback): Collection
	{
		$this->items = array_map($callback, $this->items);

		return $this;
	}

	/**
	 * Return only unique items from the collection array.
	 *
	 * @return Collection
	 */
	public function unique(): Collection
	{
		return new static(array_unique($this->items));
	}

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return Collection
	 */
	public function values(): Collection
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
		return array_map(function ($value) {
			return (is_object($value) && method_exists($value, 'toArray')) ? $value->toArray() : $value;

		}, $this->items);
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
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
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Get a CachingIterator instance.
	 *
	 * @param   integer  $flags  Caching iterator flags
	 *
	 * @return \CachingIterator
	 */
	public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator
	{
		return new \CachingIterator($this->getIterator(), $flags);
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	public function count(): int
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
	public function offsetExists($key): bool
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
	public function offsetUnset($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->toJson();
	}

	/**
	 * Get a value retrieving callback.
	 *
	 * @param   string  $value
	 *
	 * @return \Closure
	 */
	protected function valueRetriever(string $value): callable
	{
		return function ($item) use ($value) {
			return is_object($item) ? $item->{$value} : array_get($item, $value);
		};
	}

	/**
	 * Results array of items from Collection.
	 *
	 * @param   Collection|array  $items
	 *
	 * @return array
	 */
	private function getIterableItemsAsArray($items): array
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
