<?php
/**
 * @package     JCE.Plugin
 * @subpackage  Fields.Media_Jce
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (C) 2018 Ryan Demmer. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once(JPATH_PLUGINS . '/fields/media/media.php');

/**
 * Fields MediaJce Plugin
 *
 * @since  2.6.27
 */
class PlgFieldsMediaJce extends PlgFieldsMedia
{
	/**
	 * Returns the result of the media field onCustomFieldsPrepareDom method
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form)
	{		
		return parent::onCustomFieldsPrepareDom($field, $parent, $form);
	}
}