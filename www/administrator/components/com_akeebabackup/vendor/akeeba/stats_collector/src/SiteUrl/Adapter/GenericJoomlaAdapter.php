<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Joomla\CMS\Uri\Uri;

/**
 * Generic Site URL adapter for Joomla sites, version 4.2 and later.
 *
 * This is intended to be a fallback for Joomla! CLI applications when the component-specific code does not return a
 * valid URL.
 *
 * @since  1.0.0
 */
final class GenericJoomlaAdapter implements AdapterInterface
{

	/** @inheritDoc */
	public function getUrl(): string
	{
		return $this->getUrlReal() ?? '';
	}

	/** @inheritDoc */
	public function isAvailable(): bool
	{
		return defined('_JEXEC')
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

		/**
		 * Under CLI the default is to set the site URL to https://joomla.invalid/set/by/console/application so that the
		 * CLI application does not crash. If this is the case, we cannot return a valid URL.
		 *
		 * The user can pass the site's URL in the --live-site parameter.
		 */
		$possibleUrl = Uri::base();

		if (strpos($possibleUrl, 'joomla.invalid') !== false)
		{
			return null;
		}

		return $possibleUrl;
	}

}