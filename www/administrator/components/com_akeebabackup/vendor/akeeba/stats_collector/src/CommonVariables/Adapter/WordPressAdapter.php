<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\CommonVariables\Adapter;

use wpdb;

/**
 * Common variables adapter for the WordPress CMS
 *
 * @since  1.0.0
 */
final class WordPressAdapter implements AdapterInterface
{

	/**
	 * @inheritDoc
	 */
	public function getCommonVariable(string $key, ?string $default = null): ?string
	{
		$db = $this->getDatabase();

		if ($db === null)
		{
			return $default;
		}

		$tableName = $db->prefix . 'akeeba_common';

		$query = 'SELECT `value` FROM `' . $tableName . '` WHERE `key` = %s';
		$query = $db->prepare($query, $key);

		return $db->get_var($query) ?? $default;
	}

	/**
	 * @inheritDoc
	 */
	public function setCommonVariable(string $key, ?string $value): void
	{
		$db = $this->getDatabase();

		if ($db === null)
		{
			return;
		}

		$tableName = $db->prefix . 'akeeba_common';
		$data      = [
			'key'   => $key,
			'value' => $value,
		];

		$db->replace($tableName, $data);
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		return defined('WPINC') && ($this->getDatabase() !== null);
	}

	/**
	 * Get the WordPress database object
	 *
	 * @return  wpdb|null
	 * @since   1.0.0
	 */
	private function getDatabase(): ?wpdb
	{
		global $wpdb;

		if (!class_exists(wpdb::class) || !isset($wpdb) || (!$wpdb instanceof wpdb) || !$wpdb->is_mysql)
		{
			return null;
		}

		return $wpdb;
	}

}