<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\SiteUrl\Adapter;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Throwable;

/**
 * Abstract Site URL adapter for Joomla! components
 *
 * @since  1.0.0
 */
abstract class AbstractJoomlaComponentAdapter implements AdapterInterface
{
	/**
	 * The Joomla! component name
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	protected $componentName = '';

	/**
	 * The component configuration parameter storing the site's URL
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	protected $paramName = '';

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
		return defined('_JEXEC')
		       && class_exists(ComponentHelper::class)
		       && ComponentHelper::isEnabled($this->componentName)
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
			$params = ComponentHelper::getParams($this->componentName);

			return $params->get($this->paramName, null);
		}
		catch (Throwable $e)
		{
			return null;
		}
	}
}