<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Categories helper.
 *
 * @since  1.6
 */
class CategoriesHelper
{
	/**
	 * Configure the Submenu links.
	 *
	 * @param   string  $extension  The extension being used for the categories.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function addSubmenu($extension)
	{
		// Avoid nonsense situation.
		if ($extension == 'com_categories')
		{
			return;
		}

		$parts = explode('.', $extension);
		$component = $parts[0];

		if (count($parts) > 1)
		{
			$section = $parts[1];
		}

		// Try to find the component helper.
		$eName = str_replace('com_', '', $component);
		$file = JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (file_exists($file))
		{
			$prefix = ucfirst(str_replace('com_', '', $component));
			$cName = $prefix . 'Helper';

			JLoader::register($cName, $file);

			if (class_exists($cName))
			{
				if (is_callable(array($cName, 'addSubmenu')))
				{
					$lang = JFactory::getLanguage();

					// Loading language file from the administrator/language directory then
					// loading language file from the administrator/components/*extension*/language directory
					$lang->load($component, JPATH_BASE, null, false, true)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, true);

					call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
				}
			}
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   string   $extension   The extension.
	 * @param   integer  $categoryId  The category ID.
	 *
	 * @return  JObject
	 *
	 * @since   1.6
	 * @deprecated  3.2  Use JHelperContent::getActions() instead
	 */
	public static function getActions($extension, $categoryId = 0)
	{
		// Log usage of deprecated function
		try
		{
			JLog::add(
				sprintf('%s() is deprecated, use JHelperContent::getActions() with new arguments order instead.', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		// Get list of actions
		return JHelperContent::getActions($extension, 'category', $categoryId);
	}

	/**
	 * Gets a list of associations for a given item.
	 *
	 * @param   integer  $pk         Content item key.
	 * @param   string   $extension  Optional extension name.
	 *
	 * @return  array of associations.
	 */
	public static function getAssociations($pk, $extension = 'com_content')
	{
		$langAssociations = JLanguageAssociations::getAssociations($extension, '#__categories', 'com_categories.item', $pk, 'id', 'alias', '');
		$associations     = array();
		$user             = JFactory::getUser();
		$groups           = implode(',', $user->getAuthorisedViewLevels());

		foreach ($langAssociations as $langAssociation)
		{
			// Include only published categories with user access
			$arrId    = explode(':', $langAssociation->id);
			$assocId  = $arrId[0];

			$db    = \JFactory::getDbo();

			$query = $db->getQuery(true)
				->select($db->qn('published'))
				->from($db->qn('#__categories'))
				->where('access IN (' . $groups . ')')
				->where($db->qn('id') . ' = ' . (int) $assocId);

			$result = (int) $db->setQuery($query)->loadResult();

			if ($result === 1)
			{
				$associations[$langAssociation->language] = $langAssociation->id;
			}
		}

		return $associations;
	}

	/**
	 * Check if Category ID exists otherwise assign to ROOT category.
	 *
	 * @param   mixed   $catid      Name or ID of category.
	 * @param   string  $extension  Extension that triggers this function
	 *
	 * @return  integer  $catid  Category ID.
	 */
	public static function validateCategoryId($catid, $extension)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');

		$categoryTable = JTable::getInstance('Category');

		$data = array();
		$data['id'] = $catid;
		$data['extension'] = $extension;

		if (!$categoryTable->load($data))
		{
			$catid = 0;
		}

		return (int) $catid;
	}

	/**
	 * Create new Category from within item view.
	 *
	 * @param   array  $data  Array of data for new category.
	 *
	 * @return  integer
	 */
	public static function createCategory($data)
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');

		$categoryModel = JModelLegacy::getInstance('Category', 'CategoriesModel', array('ignore_request' => true));
		$categoryModel->save($data);

		$catid = $categoryModel->getState('category.id');

		return $catid;
	}
}
