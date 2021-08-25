<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Language\Wrapper;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Language\LanguageHelper;

/**
 * Wrapper class for LanguageHelper
 *
 * @since       3.4
 * @deprecated  4.0  Use `Joomla\CMS\Language\LanguageHelper` directly
 */
class LanguageHelperWrapper
{
	/**
	 * Helper wrapper method for createLanguageList
	 *
	 * @param   string   $actualLanguage  Client key for the area.
	 * @param   string   $basePath        Base path to use.
	 * @param   boolean  $caching         True if caching is used.
	 * @param   boolean  $installed       Get only installed languages.
	 *
	 * @return  array  List of system languages.
	 *
	 * @see     LanguageHelper::createLanguageList
	 * @since   3.4
	 * @deprecated  4.0  Use `Joomla\CMS\Language\LanguageHelper` directly
	 */
	public function createLanguageList($actualLanguage, $basePath = JPATH_BASE, $caching = false, $installed = false)
	{
		return LanguageHelper::createLanguageList($actualLanguage, $basePath, $caching, $installed);
	}

	/**
	 * Helper wrapper method for detectLanguage
	 *
	 * @return  string  locale or null if not found.
	 *
	 * @see     LanguageHelper::detectLanguage
	 * @since   3.4
	 * @deprecated  4.0  Use `Joomla\CMS\Language\LanguageHelper` directly
	 */
	public function detectLanguage()
	{
		return LanguageHelper::detectLanguage();
	}

	/**
	 * Helper wrapper method for getLanguages
	 *
	 * @param   string  $key  Array key
	 *
	 * @return  array  An array of published languages.
	 *
	 * @see     LanguageHelper::getLanguages
	 * @since   3.4
	 * @deprecated  4.0  Use `Joomla\CMS\Language\LanguageHelper` directly
	 */
	public function getLanguages($key = 'default')
	{
		return LanguageHelper::getLanguages($key);
	}
}
