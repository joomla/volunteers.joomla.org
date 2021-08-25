<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Categories;

\defined('JPATH_PLATFORM') or die;

/**
 * The category interface.
 *
 * @since  3.10.0
 */
interface CategoryInterface
{
	/**
	 * Loads a specific category and all its children in a CategoryNode object.
	 *
	 * @param   mixed    $id         an optional id integer or equal to 'root'
	 * @param   boolean  $forceload  True to force  the _load method to execute
	 *
	 * @return  CategoryNode|null  CategoryNode object or null if $id is not valid
	 *
	 * @since   3.10.0
	 */
	public function get($id = 'root', $forceload = false);
}
