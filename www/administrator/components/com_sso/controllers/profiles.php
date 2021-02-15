<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Profiles controller.
 *
 * @package  SSO.Component
 * @since    1.1.0
 */
class SsoControllerProfiles extends AdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.1.0
	 */
	protected $text_prefix = 'com_sso_profile';

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy  The model.
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Profile', $prefix = 'SsoModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Refresh the metadata for selected clients.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function refresh(): void
	{
		try
		{
			$clientIds = $this->input->get('cid');

			/** @var SsoModelProfile $model */
			$model = $this->getModel();
			$model->metarefresh($clientIds);
			$this->setMessage(Text::_('COM_SSO_METADATA_REFRESHED'));
		}
		catch (Exception $exception)
		{
			$this->setMessage($exception->getMessage());
		}

		$this->setRedirect('index.php?option=com_sso&view=profiles');
		$this->redirect();
	}
}
