<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Controller\Mixin\FrontEndPermissions;
use Akeeba\Backup\Site\Model\Json\Encapsulation;
use Akeeba\Backup\Site\Model\Json\Task;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use FOF30\Container\Container;
use FOF30\Model\Model;

// JSON API version number
define('AKEEBA_JSON_API_VERSION', '350');

/*
 * Short API version history:
 * 300	First draft. Basic backup working. Encryption semi-broken.
 * 316	Fixed download feature.
 * 320  Minor bug fixes
 * 330  Introduction of Akeeba Solo
 * 335  Configuration overrides in startBackup
 * 340  Advanced API allows full configuration
 * 341  exportConfiguration, importConfiguration
 */

if (!defined('AKEEBA_BACKUP_ORIGIN'))
{
	define('AKEEBA_BACKUP_ORIGIN', 'json');
}

/**
 * JSON API model. Handles remote API calls through our JSON API.
 */
class Json extends Model
{
	use FrontEndPermissions;

	const    COM_AKEEBA_CPANEL_LBL_STATUS_OK = 200; // Normal reply
	const    STATUS_NOT_AUTH = 401; // Invalid credentials
	const    STATUS_NOT_ALLOWED = 403; // Not enough privileges
	const    STATUS_NOT_FOUND = 404; // Requested resource not found
	const    STATUS_INVALID_METHOD = 405; // Unknown JSON method
	const    COM_AKEEBA_CPANEL_LBL_STATUS_ERROR = 500; // An error occurred
	const    STATUS_NOT_IMPLEMENTED = 501; // Not implemented feature
	const    STATUS_NOT_AVAILABLE = 503; // Remote service not activated

	/** @var int Data encapsulation format */
	private $encapsulationType = 1;

	/** @var  Encapsulation */
	private $encapsulation;

	/** @var string A password passed to us by the caller */
	private $password = null;

	/**
	 * Overridden constructor
	 *
	 * Sets up the encapsulation.
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 */
	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->encapsulation = new Encapsulation($this->serverKey());
	}

	/**
	 * Parses the JSON data sent by the client and executes the appropriate JSON API task
	 *
	 * @param   string  $json  The raw JSON data received from the remote client
	 *
	 * @return  string  The JSON-encoded, fully encapsulated response
	 */
	public function execute($json)
	{
		// Check if we're activated
		$enabled = $this->container->params->get('jsonapi_enabled', 0) == 1;

		// Is the Secret Key strong enough?
		$validKey = $this->serverKey();

		if (!Complexify::isStrongEnough($validKey, false))
		{
			$enabled = false;
		}

		$rawEncapsulation = $this->encapsulation->getEncapsulationByCode('ENCAPSULATION_RAW');

		if (!$this->confirmDates())
		{
			return $this->getResponse('Your version of Akeeba Backup is too old. Please update it to re-enable the remote backup and administration features.', 402);
		}

		if (!$enabled)
		{
			return $this->getResponse('Access denied', 503);
		}

		// Try to JSON-decode the request's input first
		$request = @json_decode($json, true);

		if (is_null($request))
		{
			return $this->getResponse('JSON decoding error', 500);
		}

		// Transform legacy requests
		if (!is_array($request))
		{
			$request = array(
				'encapsulation' => $rawEncapsulation,
				'body' => $request
			);
		}

		// Transform partial requests
		if (!isset($request['encapsulation']))
		{
			$request['encapsulation'] = $rawEncapsulation;
		}

		// Make sure we have a request body
		if (!isset($request['body']))
		{
			$request['body'] = '';
		}

		try
		{
			$request['body'] = $this->encapsulation->decode($request['encapsulation'], $request['body']);
		}
		catch (\Exception $e)
		{
			return $this->getResponse($e->getMessage(), $e->getCode());
		}

		// Replicate the encapsulation preferences of the client for our own output
		$this->encapsulationType = $request['encapsulation'];

		// Store the client-specified key, or use the server key if none specified and the request
		// came encrypted.
		$this->password = isset($request['body']['key']) ? $request['body']['key'] : $this->serverKey();

		// Run the method
		$params = array();

		if (isset($request['body']['data']))
		{
			$params = (array)$request['body']['data'];
		}

		try
		{
			$taskHandler = new Task($this->container);
			$data = $taskHandler->execute($request['body']['method'], $params);
		}
		catch (\RuntimeException $e)
		{
			return $this->getResponse($e->getMessage(), $e->getCode());
		}

		return $this->getResponse($data);
	}

	/**
	 * Packages the response to a JSON-encoded object, optionally encrypting the data part with a caller-supplied
	 * password.
	 *
	 * @param   mixed  $data    The response to encapsulate
	 * @param   int    $status  The status code to return. 200 = Success, anything else is treated as an error.
	 *
	 * @return  string  The JSON-encoded response
	 */
	private function getResponse($data, $status = 200)
	{
		// Initialize the response
		$response = array(
			'encapsulation' => $this->encapsulationType,
			'body'          => array(
				'status' => $status,
				'data'   => null
			)
		);

		if ($status != 200)
		{
			$response['encapsulation'] = $this->encapsulation->getEncapsulationByCode('ENCAPSULATION_RAW');
		}

		try
		{
			$response['body']['data'] = $this->encapsulation->encode($response['encapsulation'], $data, $this->password);
		}
		catch (\Exception $e)
		{
			$response['encapsulation'] = $this->encapsulation->getEncapsulationByCode('ENCAPSULATION_RAW');
			$response['body'] = array(
				'status' => $e->getCode(),
				'data' => $e->getMessage(),
			);
		}

		return '###' . json_encode($response) . '###';
	}

	/**
	 * Get the server key, i.e. the Secret Word for the front-end backups and JSON API
	 *
	 * @return  mixed
	 */
	private function serverKey()
	{
		static $key = null;

		if (is_null($key))
		{
			$key = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		}

		return $key;
	}
}
