<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Exclude model.
 *
 * @package  SSO.Component
 * @since    1.5.0
 */
class ExcludeModel extends AdminModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  1.5.0
	 */
	private $context = 'com_sso.exclude';

	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A Form object on success | False on failure.
	 *
	 * @since   1.5.0
	 *
	 * @throws  Exception
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm($this->context, 'exclude', ['control' => 'jform', 'load_data' => $loadData]);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The data for the form.
	 *
	 * @since   1.5.0
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_sso.edit.exclude.data', []);

		if (0 === count($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
}
