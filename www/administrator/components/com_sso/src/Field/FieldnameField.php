<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Field;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;

FormHelper::loadFieldClass('list');

/**
 * Select list with IDP profiles.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class FieldnameField extends ListField
{
	/**
	 * The type of field
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Fieldname';

	/**
	 * Get the list of profiles.
	 *
	 * @return  array  The list of profiles.
	 *
	 * @since   1.0.0
	 * @throws  Exception
	 */
	protected function getOptions(): array
	{
		$profileId = Factory::getApplication()->input->getInt('id', 0);
		$options   = [];

		if ($profileId)
		{
            $db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->createQuery()
				->select($db->quoteName('fieldmap'))
				->from($db->quoteName('#__sso_profiles'))
				->where($db->quoteName('id') . ' = ' . $profileId);
			$db->setQuery($query);

			$fieldMap = json_decode($db->loadResult() ?? '{}', true);


			foreach ($fieldMap['fieldMap'] as $fields)
			{
				$options[] = HTMLHelper::_('select.option', $fields['fields']['localName'], $fields['fields']['localName']);
			}
		}

		return array_merge(parent::getOptions(), $options);
	}
}
