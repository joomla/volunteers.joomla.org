<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl;

use Akeeba\UsageStats\Collector\SiteUrl\Adapter\AdapterInterface;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\AdminToolsJoomlaAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\AdminToolsWordPressAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\AkeebaBackupJoomlaAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\AkeebaBackupWordPressAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\ATSJoomlaAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\DarkLinkAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\GenericAwfAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\GenericJoomlaAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\GenericWordPressAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\PanopticonAdapter;
use Akeeba\UsageStats\Collector\SiteUrl\Adapter\SoloAdapter;

final class SiteUrl
{
	private const ADAPTERS = [
		PanopticonAdapter::class,
		DarkLinkAdapter::class,
		SoloAdapter::class,
		GenericAwfAdapter::class,
		AkeebaBackupWordPressAdapter::class,
		AdminToolsWordPressAdapter::class,
		GenericWordPressAdapter::class,
		AkeebaBackupJoomlaAdapter::class,
		AdminToolsJoomlaAdapter::class,
		ATSJoomlaAdapter::class,
		GenericJoomlaAdapter::class,
	];

	/**
	 * The adapter to get the site's URL
	 *
	 * @var   null|AdapterInterface
	 * @since 1.0.0
	 */
	private $adapter = null;

	/**
	 * Gets the URL of the current site
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function getUrl(): string
	{
		$adapter = $this->getAdapter();

		if ($adapter === null)
		{
			return '';
		}

		return $this->getAdapter()->getUrl() ?: '';
	}

	/**
	 * Get the appropriate adapter for handling common variables
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