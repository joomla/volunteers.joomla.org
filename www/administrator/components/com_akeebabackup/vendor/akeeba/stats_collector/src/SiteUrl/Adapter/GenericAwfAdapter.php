<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Uri\Uri;
use Throwable;

/**
 * Site URL adapter for generic AWF applications
 *
 * @since  1.0.0
 */
final class GenericAwfAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getUrl(): string
	{
		return $this->getUrlReal() ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return !defined('WPINC')
		       && !isset($akeebaBackupWordPressContainer)
		       && class_exists(Uri::class)
		       && class_exists(Application::class)
		       && class_exists(Container::class)
		       && !empty($this->getUrlReal());
	}

	/**
	 * Get the URL in the adapter-specific way
	 *
	 * @return  string|null  NULL if we cannot determine it
	 * @since   1.0.0
	 */
	private function getUrlReal(): ?string
	{
		if (PHP_SAPI !== 'cli')
		{
			return Uri::base();
		}

		try
		{
			return Application::getInstance()->getContainer()->appConfig->get('options.siteurl');
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}