<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\DatabaseInfo;

use Akeeba\UsageStats\Collector\Constants\DatabaseType;
use Akeeba\UsageStats\Collector\DatabaseInfo\Adapter\AdapterInterface;
use Akeeba\UsageStats\Collector\DatabaseInfo\Adapter\AkeebaEngineAdapter;
use Akeeba\UsageStats\Collector\DatabaseInfo\Adapter\DarkLinkAdapter;
use Akeeba\UsageStats\Collector\DatabaseInfo\Adapter\JoomlaAdapter;
use Akeeba\UsageStats\Collector\DatabaseInfo\Adapter\PanopticonAdapter;
use Akeeba\UsageStats\Collector\DatabaseInfo\Adapter\WordPressAdapter;

/**
 * Automated database information collection
 *
 * @since  1.0.0
 */
final class DatabaseInfo
{
	const ADAPTERS = [
		JoomlaAdapter::class,
		WordPressAdapter::class,
		AkeebaEngineAdapter::class,
		PanopticonAdapter::class,
		DarkLinkAdapter::class,
	];

	/**
	 * The adapter for getting database information
	 *
	 * @var   AdapterInterface|null
	 * @since 1.0.0
	 */
	private $adapter = null;

	/**
	 * Get the database type
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function getType(): int
	{
		$adapter = $this->getAdapter();

		if ($adapter === null)
		{
			return DatabaseType::UNKNOWN;
		}

		return $adapter->getType();
	}

	/**
	 * Get the database version
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getVersion(): string
	{
		$adapter = $this->getAdapter();

		if ($adapter === null)
		{
			return '0.0.0';
		}

		return $adapter->getVersion();
	}

	/**
	 * Get the appropriate adapter for getting database information
	 *
	 * @return  AdapterInterface|null
	 * @since   1.0.0
	 */
	private function getAdapter(): ?AdapterInterface
	{
		if ($this->adapter !== null)
		{
			return $this->adapter;
		}

		foreach (self::ADAPTERS as $className)
		{
			if (!class_exists($className))
			{
				continue;
			}

			/** @var AdapterInterface $o */
			$o = new $className;

			if (!$o->isAvailable())
			{
				continue;
			}

			return $this->adapter = $o;
		}

		return null;
	}

}