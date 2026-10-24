<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Awf\Uri\Uri;
use Solo\Container;
use Throwable;

/**
 * Site URL adapter for Akeeba Solo (standalone)
 *
 * @since  1.0.0
 */
final class SoloAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getUrl(): string
	{
		return $this->getUrlReal();
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		global $akeebaBackupWordPressContainer;
		global $akeebaSoloContainer;

		return !defined('WPINC')
		       && !isset($akeebaBackupWordPressContainer)
		       && isset($akeebaSoloContainer)
		       && class_exists(Uri::class)
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
		/** @var Container $akeebaSoloContainer */
		global $akeebaSoloContainer;

		if (PHP_SAPI !== 'cli')
		{
			return Uri::base();
		}

		try
		{
			return $akeebaSoloContainer->application->getContainer()->appConfig->get('options.siteurl');
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}