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
use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use RuntimeException;
use stdClass;

/**
 * Certificate model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class CertificateModel extends FormModel
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
		return Folder::files(PATH_SIMPLESAMLPHP . '/cert');
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
		$form = $this->loadForm($this->context, 'certificate', ['control' => 'jform', 'load_data' => $loadData]);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  stdClass
     *
     * @since   1.0.0
     *
     * @throws  Exception
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_sso.edit.certificate.data', new stdClass());

        if (is_array($data))
        {
            $item = new stdClass();

            foreach ($data as $key => $value)
            {
                $item->{$key} = $value;
            }

            $data = $item;
        }

        return $data;
    }

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 * @throws  Exception
	 * @todo    Find out how SimpleSAMLphp works with password protected certificates
	 */
	public function save($data): bool
	{
		if (!extension_loaded('openssl'))
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_OPENSSL_MISSING'));
		}

		foreach ($data['certificate'] as $value)
		{
			if (empty($value))
			{
				throw new InvalidArgumentException(Text::_('COM_SSO_MISSING_VALUE'));
			}
		}

		$dn = $data['certificate'];
		unset($dn['password']);

		// Set the rest of the options
		$privateKeyPass     = $data['certificate']['password'];
		$numberOfDays       = 3652;
		$folder             = PATH_SIMPLESAMLPHP . '/cert';
		$privateKeyFile     = $folder . '/' . basename($data['certificate']['privateKey']);
		$certificateKeyFile = $folder . '/' . basename($data['certificate']['certificateKey']);

        if (is_file($privateKeyFile))
        {
            throw new InvalidArgumentException(Text::sprintf('COM_SSO_PRIVATE_KEY_FILE_EXISTS', $privateKeyFile));
        }

        if (is_file($certificateKeyFile))
        {
            throw new InvalidArgumentException(Text::sprintf('COM_SSO_CERTIFICATE_KEY_FILE_EXISTS', $certificateKeyFile));
        }

		$attribs                     = [];
		$attribs['digest_alg']       = 'sha256';
		$attribs['private_key_bits'] = 4096;

		$privateKey = openssl_pkey_new($attribs);

		if (!$privateKey)
		{
            $errors = [];

			while ($err = openssl_error_string())
			{
				$errors[] = $err;
			}

            if (empty($errors))
            {
                $errors[] = Text::_('COM_SSO_OPENSSL_ERROR_OCCURRED');
            }

            throw new RuntimeException(implode(',', $errors));
		}

		$csr = openssl_csr_new($dn, $privateKey, $attribs);

		if (!$csr)
		{
			throw new RuntimeException(Text::_('COM_SSO_CANNOT_CREATE_CERTIFICATE'));
		}

		$signedCertificate = openssl_csr_sign($csr, null, $privateKey, $numberOfDays, $attribs);

		openssl_x509_export_to_file($signedCertificate, $certificateKeyFile);
		openssl_pkey_export_to_file($privateKey, $privateKeyFile, $privateKeyPass);

		chmod($certificateKeyFile, 0400);
		chmod($privateKeyFile, 0400);

		return true;
	}

    /**
     * Delete selected certificate files.
     *
     * @param   array  $files The certificate files to delete
     *
     * @return  bool
     *
     * @since   1.5.0
     */
    public function delete(array $files): bool
    {
        foreach ($files as $fileName)
        {
            $file = PATH_SIMPLESAMLPHP . '/cert/' . $fileName . '.pem';

            if (is_file($file))
            {
                if (!File::delete($file))
                {
                    return false;
                }
            }

            $file = PATH_SIMPLESAMLPHP . '/cert/' . $fileName . '.crt';

            if (is_file($file))
            {
                if (!File::delete($file))
                {
                    return false;
                }
            }
        }

        return true;
    }
}
