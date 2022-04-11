<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Helper;

use FOF40\Container\Container;

class Upgrade
{
	public static function getAkeebaBackup8ExtensionId(): int
	{
		return self::findExtensionId('pkg_akeeba', 'package')
			?: self::findExtensionId('com_akeeba', 'component');
	}

	/**
	 * Gets the ID of an extension
	 *
	 * @param   string  $element  Extension element, e.g. com_foo, mod_foo, lib_foo, pkg_foo or foo (CAUTION: plugin,
	 *                            file!)
	 * @param   string  $type     Extension type: component, module, library, package, plugin or file
	 *
	 * @return  int  Extension ID or 0 on failure
	 */
	private static function findExtensionId($element, $type = 'package')
	{
		$db    = Container::getInstance('com_akeeba')->db;
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . ' = ' . $db->q($element))
			->where($db->qn('type') . ' = ' . $db->q($type));

		try
		{
			$id = $db->setQuery($query, 0, 1)->loadResult();
		}
		catch (\Exception $e)
		{
			$id = 0;
		}

		return empty($id) ? 0 : (int) $id;
	}

}