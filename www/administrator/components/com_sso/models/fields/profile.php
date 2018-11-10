<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;

defined('_JEXEC') or die;

FormHelper::loadFieldClass('list');

/**
 * Select list with IDP profiles.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoFormFieldProfile extends JFormFieldList
{
	/**
	 * The type of field
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Profile';

	/**
	 * Get the list of profiles.
	 *
	 * @return  array  The list of profiles.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'alias',
						'name'
					),
					array(
						'value',
						'text'
					)
				)
			)
			->from($db->quoteName('#__sso_profiles'))
			->order($db->quoteName('ordering'));
		$db->setQuery($query);

		$applications = $db->loadObjectList();

		return array_merge(parent::getOptions(), $applications);
	}
}
