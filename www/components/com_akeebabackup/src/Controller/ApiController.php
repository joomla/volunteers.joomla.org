<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use Exception;
use Joomla\Application\AbstractWebApplication;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use JsonSerializable;
use RuntimeException;

class ApiController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	/**
	 * Secret Key (cached for quicker retrieval)
	 *
	 * @var   null|string
	 * @since 7.4.0
	 */
	private $key = null;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');

		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			define('AKEEBA_BACKUP_ORIGIN', 'json');
		}

		/**
		 * This front-end endpoint implements the v2 access model — the Akeeba Backup Secret Word is both the
		 * authentication and the authorisation. Tell the backup tasks so they can warn about its deprecation.
		 *
		 * @deprecated 11.0 Use the v3 API through the Joomla API application instead.
		 */
		if (!defined('AKEEBA_BACKUP_JSON_API_VERSION'))
		{
			define('AKEEBA_BACKUP_JSON_API_VERSION', 2);
		}
	}

	public function main()
	{
		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			define('AKEEBA_BACKUP_ORIGIN', 'json');
		}

		$outputBuffering = function_exists('ob_start') && function_exists('ob_end_clean');

		// Use the model to parse the JSON message
		if ($outputBuffering)
		{
			@ob_start();
		}

		try
		{
			if (!$this->verifyKey())
			{
				throw new RuntimeException("Access denied", 503);
			}

			$httpVerb = $this->input->getMethod() ?? 'GET';

			switch ($httpVerb)
			{
				case 'GET':
					$method = $this->input->get->getCmd('method', '');
					$input  = $this->input->get;
					break;

				case 'POST':
					$method = $this->input->post->getCmd('method', '');
					$input  = $this->input->post;
					break;

				default:
					throw new RuntimeException("Invalid HTTP method {$httpVerb}", 405);
					break;
			}

			$taskHandler = new Task($this->factory);

			$cleanedData = $input->getArray();
			$data        = [];

			foreach ($cleanedData as $key => $value)
			{
				if (is_array($value))
				{
					$data[$key] = $input->get($key, [], 'array');
				}
				else
				{
					$data[$key] = $input->get($key, null, 'raw');
				}
			}

			$result = [
				'status' => 200,
				'data'   => $taskHandler->execute($method, $data),
			];
		}
		catch (Exception $e)
		{
			$result = [
				'status' => $e->getCode(),
				'data'   => $e->getMessage(),
			];

			// When site debugging is enabled AND error reporting is set to maximum we'll return exception traces
			$siteDebug         = (bool) Factory::getApplication()->get('debug');
			$maxErrorReporting = Factory::getApplication()->get('error_reporting') === 'maximum';

			if ($siteDebug && $maxErrorReporting)
			{
				$result['debug'] = [];
				$thisException   = $e;

				while (!empty($thisException))
				{
					$result['debug'][] = [
						'message'   => $thisException->getMessage(),
						'code'      => $thisException->getCode(),
						'file'      => $thisException->getFile(),
						'line'      => $thisException->getLine(),
						'backtrace' => $thisException->getTrace(),
					];

					$thisException = $e->getPrevious();
				}
			}
		}

		if ($outputBuffering)
		{
			@ob_end_clean();
		}

		/** @var JsonDocument $doc */
		$doc = $this->app->getDocument();

		if (!($doc instanceof JsonDocument))
		{
			$this->workaroundResponse($result);
		}

		/** @var AbstractWebApplication $app */
		$app = Factory::getApplication();
		$app->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);
		$app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
		$app->setHeader('Pragma', 'no-cache', true);

		$doc->setName('akeeba');

		$jsonOptions = (defined('JDEBUG') && JDEBUG) ? JSON_PRETTY_PRINT : 0;

		echo json_encode($result, $jsonOptions);
	}

	/**
	 * Send a JSON response when format=html or anything other than json
	 *
	 * @param   JsonSerializable|array  $result
	 *
	 * @throws Exception
	 *
	 * @since  7.4.0
	 */
	private function workaroundResponse($result): void
	{
		// Disable caching
		@header('Expires: Wed, 17 Aug 2005 00:00:00 GMT', true);
		@header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
		@header('Pragma: no-cache', true);

		// JSON content
		@header('Content-Type: application/json; charset=utf-8', true);
		@header('Content-Disposition: attachment; filename="joomla.json"', true);

		$jsonOptions = (defined('JDEBUG') && JDEBUG) ? JSON_PRETTY_PRINT : 0;

		echo json_encode($result, $jsonOptions);

		Factory::getApplication()->close();
	}

	/**
	 * Verifies the Secret Key (API token)
	 *
	 * @return  bool
	 * @since   7.4.0
	 */
	private function verifyKey(): bool
	{
		$cParams = ComponentHelper::getParams('com_akeebabackup');

		// Is the JSON API enabled?
		if ($cParams->get('jsonapi_enabled', 0) != 1)
		{
			return false;
		}

		// Is the key secure enough?
		$validKey = $this->serverKey();

		if (empty($validKey) || empty(trim($validKey)) || !Complexify::isStrongEnough($validKey, false))
		{
			return false;
		}

		/**
		 * Get the API authentication token. There are two sources
		 * 1. X-Akeeba-Auth header (preferred, overrides all others)
		 * 2. the _akeebaAuth GET parameter
		 */
		$authSource = $this->input->server->getString('HTTP_X_AKEEBA_AUTH', null);

		if (is_null($authSource))
		{
			$authSource = $this->input->get->getString('_akeebaAuth', null);
		}

		// No authentication token? No joy.
		if (empty($authSource) || !is_string($authSource) || empty(trim($authSource)))
		{
			return false;
		}

		return hash_equals($validKey, $authSource);
	}

	/**
	 * Get the server key, i.e. the Secret Word for the front-end backups and JSON API
	 *
	 * @return  mixed
	 *
	 * @since   7.4.0
	 */
	private function serverKey()
	{
		if (is_null($this->key))
		{
			$this->key = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		}

		return $this->key;
	}
}