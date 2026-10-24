<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables\Adapter;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Throwable;

/**
 * Common variables adapter for the Joomla CMS, version 4 or later
 *
 * @since 1.0.0
 */
final class JoomlaAdapter implements AdapterInterface
{
	/**
	 * @inheritDoc
	 */
	public function getCommonVariable(string $key, ?string $default = null): ?string
	{
		$db = $this->getDatabase();

		if (!$db instanceof DatabaseInterface)
		{
			return $default;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('value'))
			->from($db->qn('#__akeeba_common'))
			->where($db->qn('key') . ' = :key')
			->bind(':key', $key);

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

		if (!$db instanceof DatabaseInterface)
		{
			return;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('COUNT(*)')
			->from($db->qn('#__akeeba_common'))
			->where($db->qn('key') . ' = :key')
			->bind(':key', $key);

		try
		{
			$db->setQuery($query);
			$count = $db->loadResult();
		}
		catch (Throwable $e)
		{
			return;
		}

		try
		{
			$commonVariableObject = (object) [
				'key'   => $key,
				'value' => $value,
			];

			if (!$count)
			{
				$db->insertObject('#__akeeba_common', $commonVariableObject);
			}
			else
			{
				$db->updateObject('#__akeeba_common', $commonVariableObject, 'key');
			}
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
		return defined('JVERSION')
		       && version_compare(JVERSION, '4.0.0', 'ge')
		       && defined('_JEXEC')
		       && interface_exists(DatabaseInterface::class)
		       && class_exists(Factory::class)
		       && ($this->getDatabase() instanceof DatabaseInterface);
	}

	/**
	 * Get the Joomla database object
	 *
	 * @return  DatabaseInterface|null
	 * @since   1.0.0
	 */
	private function getDatabase(): ?DatabaseInterface
	{
		try
		{
			return Factory::getContainer()->get(DatabaseInterface::class);
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}