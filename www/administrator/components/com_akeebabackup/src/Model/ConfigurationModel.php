<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Base;
use Akeeba\Engine\Util\Transfer\Ftp;
use Akeeba\Engine\Util\Transfer\FtpCurl;
use Akeeba\Engine\Util\Transfer\Sftp;
use Akeeba\Engine\Util\Transfer\SftpCurl;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;
use RuntimeException;

#[\AllowDynamicProperties]
class ConfigurationModel extends BaseModel
{
	/**
	 * Method to get state variables. Uses application input if the state is not set.
	 *
	 * @param   null   $property  Optional parameter name
	 * @param   mixed  $default   Optional default value
	 *
	 * @return  mixed  The property where specified, the state object where omitted
	 *
	 * @since   4.0.0
	 */
	public function getState($property = null, $default = null)
	{
		try
		{
			$default = JoomlaFactory::getApplication()->getInput()
				->get($property, $default, is_array($default) ? 'array' : 'raw');
		}
		catch (Exception $e)
		{
		}

		return parent::getState($property, $default);
	}

	/**
	 * Save the engine configuration
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function saveEngineConfig(): void
	{
		/** @var CMSApplication $app */
		$app  = JoomlaFactory::getApplication();
		$data = $this->getState('engineconfig', []);

		// Forbid stupidly selecting the site's root as the output or temporary directory
		if (array_key_exists('akeeba.basic.output_directory', $data))
		{
			$folder = $data['akeeba.basic.output_directory'];
			$folder = Factory::getFilesystemTools()->translateStockDirs($folder, true, true);
			$check  = Factory::getFilesystemTools()->translateStockDirs('[SITEROOT]', true, true);

			if ($check == $folder)
			{
				$data['akeeba.basic.output_directory'] = '[DEFAULT_OUTPUT]';
			}
			else
			{
				$data['akeeba.basic.output_directory'] = Factory::getFilesystemTools()->rebaseFolderToStockDirs($data['akeeba.basic.output_directory']);
			}
		}

		// Unprotect the configuration and merge it
		$config        = Factory::getConfiguration();
		$protectedKeys = $config->getProtectedKeys();
		$config->resetProtectedKeys();
		$config->mergeArray($data, false, false);
		$config->setProtectedKeys($protectedKeys);

		// Save configuration
		Platform::getInstance()->save_configuration();
	}

	/**
	 * Test the FTP connection.
	 *
	 * @return  void
	 * @throws  RuntimeException
	 */
	public function testFTP(): void
	{
		$config = [
			'host'        => $this->getState('host'),
			'port'        => $this->getState('port'),
			'username'    => $this->getState('user'),
			'password'    => $this->getState('pass'),
			'directory'   => $this->getState('initdir'),
			'ssl'         => $this->getState('usessl'),
			'passive'     => $this->getState('passive'),
			'passive_fix' => $this->getState('passive_mode_workaround'),
		];

		// Check for bad settings
		if (substr($config['host'], 0, 6) == 'ftp://')
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_CONFIG_FTPTEST_BADPREFIX'), 500);
		}

		// Special case for cURL transport
		if ($this->getState('isCurl'))
		{
			$test = new FtpCurl($config);
		}
		else
		{
			$test = new Ftp($config);
		}

		$test->connect();

		// If we're here, it means that  we were able to connect to the remote server. Now let's try to upload a small file
		$tmp_path  = JoomlaFactory::getApplication()->get('tmp_path');
		$test_file = '.akeeba_test_' . substr(hash('md5', microtime()), 0, 5). '.dat';
		$tmp_file  = $tmp_path . '/' . $test_file;
		file_put_contents($tmp_file, 'Akeeba Backup test file');

		// Construct the remote file path
		$realdir  = substr($config['directory'], -1) == '/' ? substr($config['directory'], 0, strlen($config['directory']) - 1) : $config['directory'];
		$realdir  .= '/' . dirname($test_file);
		$realdir  = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;
		$realname = $realdir . '/' . basename($test_file);

		try
		{
			$ret = $test->upload($tmp_file, $realname);
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_CONFIG_FTPTEST_NOUPLOAD'), 500);
		}
		finally
		{
			try
			{
				File::delete($tmp_file);
			}
			catch (Exception $e)
			{
				// Swallow.
			}
		}

		if (!$ret)
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_CONFIG_FTPTEST_NOUPLOAD'), 500);
		}

		// Delete the remote file. If it fails, that's ok for us
		$test->delete($realname);
	}

	/**
	 * Test the SFTP connection.
	 *
	 * @return  void
	 * @throws  RuntimeException
	 */
	public function testSFTP(): void
	{
		$config = [
			'host'       => $this->getState('host'),
			'port'       => $this->getState('port'),
			'username'   => $this->getState('user'),
			'password'   => $this->getState('pass'),
			'privateKey' => $this->getState('privkey'),
			'publicKey'  => $this->getState('pubkey'),
			'directory'  => $this->getState('initdir'),
		];

		// Check for bad settings
		if (substr($config['host'], 0, 7) == 'sftp://')
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_CONFIG_SFTPTEST_BADPREFIX'), 500);
		}

		// Initialize the correct object
		if ($this->getState('isCurl'))
		{
			$test = new SftpCurl($config);
		}
		else
		{
			$test = new Sftp($config);
		}

		$test->connect();

		// If we're here, it means that  we were able to connect to the remote server. Now let's try to upload a small file
		$tmp_path  = JoomlaFactory::getApplication()->get('tmp_path');
		$test_file = '.akeeba_test_' . substr(hash('md5', microtime()), 0, 5). '.dat';
		$tmp_file  = $tmp_path . '/' . $test_file;
		file_put_contents($tmp_file, 'Akeeba Backup test file');

		// Construct the remote file path
		$realdir  = substr($config['directory'], -1) == '/' ? substr($config['directory'], 0, strlen($config['directory']) - 1) : $config['directory'];
		$realdir  .= '/' . dirname($test_file);
		$realdir  = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;
		$realname = $realdir . '/' . basename($test_file);

		try
		{
			$test->upload($tmp_file, $realname);
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_CONFIG_SFTPTEST_NOUPLOAD'), 500);
		}
		finally
		{
			try
			{
				File::delete($tmp_file);
			}
			catch (Exception $e)
			{
				// Swallow.
			}
		}

		// Delete the remote file. If it fails, that's ok for us
		$test->delete($realname);
	}

	/**
	 * Opens an OAuth window for the selected post-processing engine
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function dpeOuthOpen(): void
	{
		$engine = $this->getState('engine');
		$params = $this->getState('params', []);

		// Get a callback URI for OAuth 2
		$params['callbackURI'] = rtrim(Uri::base(), '/') . '/index.php?option=com_akeebabackup&view=Configuration&task=dpecustomapiraw&engine=' . $engine;

		// Get the Input object
		$params['input'] = JoomlaFactory::getApplication()->getInput()->getArray();

		// Get the engine
		$engineObject = Factory::getPostprocEngine($engine);

		if (!$engineObject instanceof Base)
		{
			return;
		}

		$engineObject->oauthOpen($params);
	}

	/**
	 * Runs a custom API call for the selected post-processing engine
	 *
	 * @return  mixed
	 */
	public function dpeCustomAPICall()
	{
		$engine = $this->getState('engine');
		$method = $this->getState('method');
		$params = $this->getState('params', []);

		// Get the Input object
		$params['input'] = JoomlaFactory::getApplication()->getInput()->getArray();

		$engineObject = Factory::getPostprocEngine($engine);

		if (!$engineObject instanceof Base)
		{
			return false;
		}

		return $engineObject->customAPICall($method, $params);
	}

	/**
	 * Test the connection to a remote FTP server using cURL transport
	 *
	 * @return  void
	 * @throws  RuntimeException
	 */
	private function testFtpCurl(): void
	{
		$options = [
			'host'        => $this->getState('host'),
			'port'        => $this->getState('port'),
			'username'    => $this->getState('user'),
			'password'    => $this->getState('pass'),
			'directory'   => $this->getState('initdir'),
			'ssl'         => $this->getState('usessl'),
			'passive'     => $this->getState('passive'),
			'passive_fix' => $this->getState('passive_mode_workaround'),
		];

		$sftpTransfer = new FtpCurl($options);

		$sftpTransfer->connect();
	}
}