<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables;

use Akeeba\UsageStats\Collector\CommonVariables\Adapter\AdapterInterface;
use Akeeba\UsageStats\Collector\CommonVariables\Adapter\AdminToolsWPAdapter;
use Akeeba\UsageStats\Collector\CommonVariables\Adapter\AkeebaEngineAdapter;
use Akeeba\UsageStats\Collector\CommonVariables\Adapter\DarkLinkAdapter;
use Akeeba\UsageStats\Collector\CommonVariables\Adapter\JoomlaAdapter;
use Akeeba\UsageStats\Collector\CommonVariables\Adapter\PanopticonAdapter;
use Akeeba\UsageStats\Collector\CommonVariables\Adapter\WordPressAdapter;

/**
 * A utility class to get common variables across Akeeba software in a CMS / database installation.
 *
 * @since  1.0.0
 */
final class CommonVariables
{
	private const ADAPTERS = [
		PanopticonAdapter::class,
		DarkLinkAdapter::class,
		AdminToolsWPAdapter::class,
		JoomlaAdapter::class,
		WordPressAdapter::class,
		AkeebaEngineAdapter::class,
	];

	/**
	 * The adapter to interact with the common variables
	 *
	 * @var   null|AdapterInterface
	 * @since 1.0.0
	 */
	private $adapter = null;

	/**
	 * Load a variable from the common variables table. If it does not exist, it returns the default value
	 *
	 * @param   string  $key      The key to retrieve
	 * @param   mixed   $default  The default value in case the key is missing (default: NULL)
	 *
	 * @return  string|null  The value of the common variable; NULL if it does not exist
	 * @since   1.0.0
	 */
	public function getCommonVariable(string $key, ?string $default = null): ?string
	{
		$adapter = $this->getAdapter();

		return ($adapter === null) ? $default : $adapter->getCommonVariable($key, $default);
	}

	/**
	 * Set a variable to the common variables table.
	 *
	 * @param   string       $key    The key to set
	 * @param   string|null  $value  The value to set
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function setCommonVariable(string $key, ?string $value): void
	{
		$adapter = $this->getAdapter();

		if (empty($adapter))
		{
			return;
		}

		$adapter->setCommonVariable($key, $value);
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