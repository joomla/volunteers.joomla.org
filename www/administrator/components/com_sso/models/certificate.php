<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;

/**
 * Certificate model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoModelCertificate extends FormModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $context = 'com_sso.certificate';

	/**
	 * Get a list of certificates.
	 *
	 * @return  array  List of certificates.
	 *
	 * @since   1.0.0
	 */
	public function getCertificates(): array
	{
		return Folder::files(JPATH_LIBRARIES . '/simplesamlphp/cert');
	}

	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A Form object on success | False on failure.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->context, 'certificate', ['control' => 'jform', 'load_data' => $loadData]);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function save($data): bool
	{
		// Check if openSSL is available
		if (!extension_loaded('openssl'))
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_OPENSSL_MISSING'));
		}

		// Check if all fields are filled in
		foreach ($data['certificate'] as $value)
		{
			if (empty($value))
			{
				throw new InvalidArgumentException(Text::_('COM_SSO_MISSING_VALUE'));
			}
		}

		// Set the Distinguished Name fields
		$dn = $data['certificate'];
		unset($dn['password']);

		// Set the rest of the options
		$privateKeyPass = $data['certificate']['password'];
		$numberOfDays   = 3652;
		$folder         = JPATH_LIBRARIES . '/simplesamlphp/cert';
		$privateKey     = $folder . '/' . basename($data['certificate']['privateKey']);
		$certificateKey = $folder . '/' . basename($data['certificate']['certificateKey']);

		// We only generate SEPA keys
		$attribs                     = array();
		$attribs['digest_alg']       = 'sha256';
		$attribs['private_key_bits'] = 4096;

		$privkey = openssl_pkey_new($attribs);

		if (!$privkey)
		{
			while ($err = openssl_error_string())
			{
				$errs[] = $err;
			}
		}

		$csr = openssl_csr_new($dn, $privkey, $attribs);

		if (!$csr)
		{
			throw new RuntimeException(Text::_('COM_SSO_CANNOT_CREATE_CERTIFICATE'));
		}

		$sscert = openssl_csr_sign($csr, null, $privkey, $numberOfDays, $attribs);

		openssl_x509_export_to_file($sscert, $certificateKey);
		openssl_pkey_export_to_file($privkey, $privateKey, $privateKeyPass);

		return true;
	}
}
