<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Controller;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Site\Model\Json\Task;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use Exception;
use FOF40\Container\Container;
use FOF40\Controller\Controller;
use FOF40\Controller\Mixin\PredefinedTaskList;
use FOF40\Input\Input;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Factory;
use JsonSerializable;

/**
 * API version
 *
 * 400: First JSON API v2 implementation
 */
if (!defined('AKEEBA_JSON_API_VERSION'))
{
	define('AKEEBA_JSON_API_VERSION', 400);
}

/**
 * Akeeba Backup JSON API v2
 *
 * @since   7.4.0
 */
class Api extends Controller
{
	use PredefinedTaskList;

	/**
	 * Secret Key (cached for quicker retrieval)
	 *
	 * @var   null|string
	 * @since 7.4.0
	 */
	private $key = null;

	/**
	 * Overridden constructor
	 *
	 * @param   Container  $container  The application container
	 * @param   array      $config     The configuration array
	 *
	 * @since   7.4.0
	 */
	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['main']);
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
				throw new \RuntimeException("Access denied", 503);
			}

			$httpVerb = $this->input->getMethod() ?? 'GET';

			switch ($httpVerb)
			{
				case 'GET':
					$method = $this->input->get->getCmd('method', '');
					$input  = new Input('GET');
					break;

				case 'POST':
					$method = $this->input->post->getCmd('method', '');
					$input  = new Input('POST');
					break;

				default:
					throw new \RuntimeException("Invalid HTTP method {$httpVerb}", 405);
					break;
			}

			if (class_exists('Joomla\CMS\Component\ComponentHelper') && \Joomla\CMS\Component\ComponentHelper::isEnabled('com_akeebabackup'))
			{
				throw new \RuntimeException(sprintf('Please finish upgrading to Akeeba Backup 9 and uninstall Akeeba Backup 8 per the instructions shown on your site\'s backend, Components, Akeeba Backup'), 400);
			}

			$taskHandler = new Task($this->container);

			$result = [
				'status' => 200,
				'data' => $taskHandler->execute($method, $input->getData())
			];
		}
		catch (Exception $e)
		{
			$result = [
				'status' => $e->getCode(),
				'data'   => $e->getMessage(),
			];
		}

		if ($outputBuffering)
		{
			@ob_end_clean();
		}

		/** @var JsonDocument $doc */
		$doc = Document::getInstance('json');

		if (!($doc instanceof JsonDocument))
		{
			$this->workaroundResponse($result);
		}

		// Force cache busting
		$app = $this->container->platform;
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
		// Is the JSON API enabled?
		if ($this->container->params->get('jsonapi_enabled', 0) != 1)
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
