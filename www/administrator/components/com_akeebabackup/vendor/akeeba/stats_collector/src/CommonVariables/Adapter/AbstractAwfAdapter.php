<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables\Adapter;

use Awf\Container\Container;
use Throwable;

/**
 * Abstract common variables interaction adapter for AWF application
 *
 * @since  1.0.0
 */
abstract class AbstractAwfAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getCommonVariable(string $key, ?string $default = null): ?string
	{
		try
		{
			$db = $this->getContainer()->db;
		}
		catch (Throwable $e)
		{
			return $default;
		}

		if ($db === null)
		{
			return $default;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('value'))
			->from($db->quoteName('#__akeeba_common'))
			->where($db->quoteName('key') . ' = ' . $db->quote($key));

		try
		{
			$db->setQuery($query);
			$result = $db->loadResult();
		}
		catch (Throwable $e)
		{
			$result = $default;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function setCommonVariable(string $key, ?string $value): void
	{

		try
		{
			$db = $this->getContainer()->db;
		}
		catch (Throwable $e)
		{
			return;
		}

		if ($db === null)
		{
			return;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('COUNT(*)')
			->from($db->quoteName('#__akeeba_common'))
			->where($db->quoteName('key') . ' = ' . $db->quote($key));

		try
		{
			$db->setQuery($query);
			$count = $db->loadResult();
		}
		catch (Throwable $e)
		{
			return;
		}

		if (!$count)
		{
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->insert($db->quoteName('#__akeeba_common'))
				->columns([$db->quoteName('key'), $db->quoteName('value')])
				->values($db->quote($key) . ', ' . $db->quote($value));
		}
		else
		{
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->update($db->quoteName('#__akeeba_common'))
				->set($db->quoteName('value') . ' = ' . $db->quote($value))
				->where($db->quoteName('key') . ' = ' . $db->quote($key));
		}

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Throwable $e)
		{
		}
	}

	/**
	 * Returns the AWF container for the specific application
	 *
	 * @return  Container
	 * @since   1.0.0
	 */
	protected abstract function getContainer(): Container;
}