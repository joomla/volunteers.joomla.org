<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables\Adapter;

use Akeeba\Engine\Driver\Base as BaseDriver;
use Akeeba\Engine\Factory;
use Throwable;

final class AkeebaEngineAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getCommonVariable(string $key, ?string $default = null): ?string
	{
		$db = $this->getDatabase();

		if ($db === null)
		{
			return $default;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('value'))
			->from($db->qn('#__akeeba_common'))
			->where($db->qn('key') . ' = ' . $db->q($key));

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
		$db = $this->getDatabase();

		if ($db === null)
		{
			return;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('COUNT(*)')
			->from($db->qn('#__akeeba_common'))
			->where($db->qn('key') . ' = ' . $db->q($key));

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
				->insert($db->qn('#__akeeba_common'))
				->columns([$db->qn('key'), $db->qn('value')])
				->values($db->q($key) . ', ' . $db->q($value));
		}
		else
		{
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->update($db->qn('#__akeeba_common'))
				->set($db->qn('value') . ' = ' . $db->q($value))
				->where($db->qn('key') . ' = ' . $db->q($key));
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
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('AKEEBAENGINE')
		       && class_exists(Factory::class)
		       && class_exists(BaseDriver::class);
	}

	/**
	 * Get the database driver from the Akeeba Engine (backup engine)
	 *
	 * @return  BaseDriver|null
	 * @since   1.0.0
	 */
	private function getDatabase(): ?BaseDriver
	{
		try
		{
			return Factory::getDatabase();
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}