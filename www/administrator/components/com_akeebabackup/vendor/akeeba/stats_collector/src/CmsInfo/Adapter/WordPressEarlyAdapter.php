<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CmsInfo\Adapter;

use Akeeba\UsageStats\Collector\Constants\CmsType;
use Throwable;

/**
 * Collect CMS information adapter for Joomla sites, version 4 and later
 */
final class WordPressEarlyAdapter implements AdapterInterface
{

	/** @inheritDoc */
	public function getType(): int
	{
		if (function_exists('classicpress_version'))
		{
			return CmsType::CLASSICPRESS;
		}

		return CmsType::WORDPRESS;
	}

	/** @inheritDoc */
	public function getVersion(): string
	{
		return $this->getWPVersion() ?? '0.0.0';
	}

	/** @inheritDoc */
	public function isAvailable(): bool
	{
		return defined('WPINC') && defined('ABSPATH') && $this->getWPVersion() !== false;
	}

	/**
	 * Try to get the WordPress version by including its version.php file directly.
	 *
	 * @return  string|null  The WordPress version. NULL when it cannot be determined.
	 * @since   1.0.0
	 */
	private function getWPVersion(): ?string
	{
		$filePath = ABSPATH . '/' . WPINC . '/version.php';

		if (!@file_exists($filePath) || !@is_file($filePath) || !@is_readable($filePath))
		{
			return null;
		}

		try
		{
			include $filePath;
		}
		catch (Throwable $e)
		{
			return null;
		}

		if (function_exists('classicpress_version'))
		{
			/** @noinspection PhpUndefinedFunctionInspection */
			return classicpress_version();
		}

		if (isset($cp_version))
		{
			return $cp_version;
		}

		/** @noinspection PhpUndefinedVariableInspection */
		return $wp_version;
	}
}