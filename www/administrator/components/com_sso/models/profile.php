<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Profile model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoModelProfile extends AdminModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $context = 'com_sso.profile';

	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A Form object on success | False on failure.
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->context, 'profile', array('control' => 'jform', 'load_data' => $loadData));

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  \JObject|boolean  Object on success, false on failure.
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		$item->params         = $this->loadServiceProviderSettings($item->alias);
		$item->params->fields = json_decode($item->fieldmap);

		return $item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   string  $provider  The name of the payment provider to load the form for.
	 * @param   string  $alias     The provider alias
	 *
	 * @return  mixed  A JForm object on success, false on failure.
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function getProviderForm($provider, $alias = 'default-sp')
	{
		/** @var Form $form */
		$form = $this->loadForm($this->context . '.' . $provider, $provider, array('control' => 'jform', 'load_data' => false));

		if (!is_object($form))
		{
			return false;
		}

		// Load the data
		$data = $this->loadServiceProviderSettings($alias);

		if ($data)
		{
			$form->bind($data);
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form..
	 *
	 * @return  array  The data for the form..
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_sso.edit.profile.data', array());

		if (0 === count($data))
		{
			// Check which data we need to load
			$data         = $this->getItem();
			$data->joomla = null;

			if (is_array($data->params) && array_key_exists('joomla', $data->params))
			{
				$data->joomla = $data->params['joomla'];
			}
		}

		return $data;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form    $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     \JFormRule
	 * @see     \JFilterInput
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function validate($form, $data, $group = null)
	{
		// Filter and validate the form data.
		$profileData = $form->filter($data);
		$return      = $form->validate($profileData, $group);

		// Check for an error.
		if ($return instanceof Exception)
		{
			$this->setError($return->getMessage());

			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$this->setError($message);
			}

			return false;
		}

		// Filter and validate the provider form data
		$form         = $this->getProviderForm('serviceprovider');
		$providerData = $form->filter($data);
		$return       = $form->validate($providerData, $group);

		// Check for an error.
		if ($return instanceof Exception)
		{
			$this->setError($return->getMessage());

			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$this->setError($message);
			}

			return false;
		}

		$data = array_merge($profileData, $providerData);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{
		// Add the Joomla configuration to the profile
		$data['params'] = json_encode(array('joomla' => $data['joomla']));

		// Encode the fieldmap
		$data['fieldmap'] = json_encode($data['fields']);

		// Generate the alias for new profiles
		if ((int) $data['id'] === 0)
		{
			$data['alias'] = ApplicationHelper::stringURLSafe($data['name']);
		}

		// Save the profile
		/** @var \Joomla\CMS\Table\Table $table */
		$table = $this->getTable();
		$table->save($data);
		$this->setState($this->getName() . '.id', $table->id);

		// Save the authsources.php
		$this->saveServiceProviderAuthsources($data['authorization'], $data['alias']);

		return true;
	}

	/**
	 * Load the settings for a service provider profile.
	 *
	 * @param   string  $alias  The provider alias to load the settings for
	 *
	 * @return  object  List of settings.
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	private function loadServiceProviderSettings($alias = 'default-sp')
	{
		$data                = new stdClass;
		$data->joomla        = new stdClass;
		$data->authorization = new stdClass;

		// Require the SimpleSAMLphp authorization sources
		require JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		/** @var $config array */
		if (!array_key_exists($alias, $config))
		{
			$config[$alias] = array();
		}

		$data->authorization->idp         = array_key_exists('idp', $config[$alias]) ? $config[$alias]['idp'] : '';
		$data->authorization->privatekey  = array_key_exists('privatekey', $config[$alias]) ? $config[$alias]['privatekey'] : '';
		$data->authorization->certificate = array_key_exists('certificate', $config[$alias]) ? $config[$alias]['certificate'] : '';

		// Load the Joomla config
		$data->joomla = array();
		$db           = $this->getDbo();
		$query        = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('id') . ' = ' . (int) $this->getState($this->getName() . '.id'));
		$db->setQuery($query);
		$params = (new Registry($db->loadResult()))->toArray();

		if (array_key_exists('joomla', $params))
		{
			$data->joomla = $params['joomla'];
		}

		return $data;
	}

	/**
	 * Method to save the authorization data.
	 *
	 * @param   array   $data   The form data
	 * @param   string  $alias  The provider alias
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function saveServiceProviderAuthsources($data, $alias = 'default-sp')
	{
		// Require the SimpleSAMLphp authorization sources
		require JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		/** @var $config array */
		if (!array_key_exists('admin', $config))
		{
			$config['admin'] = array('core:AdminPassword');
		}

		if (!array_key_exists($alias, $config))
		{
			$config[$alias] = array(
				'saml:SP',
				'idp'                        => '',
				'discoURL'                   => null,
				'NameIDPolicy'               => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
				'privatekey'                 => '',
				'certificate'                => '',
				'sign.logout'                => true,
				'signature.algorithm'        => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
				'SingleLogoutServiceBinding' => array(
					'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
					'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
					'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
				),
				'attributes'                 => array(
					'Name'           => 'name',
					'E-Mail Address' => 'emailaddress',
					'Username'       => 'upn',
				),
				'attributes.NameFormat'      => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
				'attributes.required'        => array(
					'name',
					'emailaddress',
					'upn'
				),
			);
		}

		// Set the user settings
		$config[$alias]['idp']         = $data['idp'];
		$config[$alias]['privatekey']  = $data['privatekey'];
		$config[$alias]['certificate'] = $data['certificate'];

		// Write the data to the configuration file
		$config = var_export($config, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		file_put_contents($filename, '<?php $config = ' . $config . ';');
	}
}
