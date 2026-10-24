<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CmsInfo;

use Akeeba\UsageStats\Collector\CmsInfo\Adapter\AdapterInterface;
use Akeeba\UsageStats\Collector\CmsInfo\Adapter\JoomlaAdapter;
use Akeeba\UsageStats\Collector\CmsInfo\Adapter\WordPressAdapter;
use Akeeba\UsageStats\Collector\CmsInfo\Adapter\WordPressEarlyAdapter;
use Akeeba\UsageStats\Collector\Constants\CmsType;

final class CmsInfo
{
	private const ADAPTERS = [
		JoomlaAdapter::class,
		WordPressAdapter::class,
		WordPressEarlyAdapter::class,
	];

	/**
	 * The adapter for getting CMS information
	 *
	 * @var   AdapterInterface|null
	 * @since 1.0.0
	 */
	private $adapter = null;

	/**
	 * Get the CMS type
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function getType(): int
	{
		$adapter = $this->getAdapter();

		if ($adapter === null)
		{
			return CmsType::STANDALONE;
		}

		return $adapter->getType();
	}

	/**
	 * Get the CMS version
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
	 * Get the appropriate adapter for getting CMS information
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