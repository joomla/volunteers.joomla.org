<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Site\Controller\Mixin\FrontEndPermissions;
use Akeeba\Backup\Site\Model\Json\Encapsulation;
use Akeeba\Backup\Site\Model\Json\Task;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use FOF40\Container\Container;
use FOF40\Model\Model;

// JSON API version number
define('AKEEBA_JSON_API_VERSION', '400');

/*
 * Short API version history:
 * 300	First draft. Basic backup working. Encryption semi-broken.
 * 316	Fixed download feature.
 * 320  Minor bug fixes
 * 330  Introduction of Akeeba Solo
 * 335  Configuration overrides in startBackup
 * 340  Advanced API allows full configuration
 * 350  exportConfiguration, importConfiguration
 * 400  API version 2
 *
 * Notes:
 *
 * When support for non-Raw encapsulations was removed December 2019 the API level was left at 350 (same
 * since May 2016). If you see API level 350 try using only ever using the RAW encapsulation.
 *
 * If you see API level 400 or greater you SHOULD try using JSON API v2. The legacy JSON API will eventually go away.
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

	/** @var int Normal reply */
	public const    COM_AKEEBA_CPANEL_LBL_STATUS_OK = 200;

	/** @var int Invalid credentials */
	public const    STATUS_NOT_AUTH = 401;

	/** @var int Not enough privileges */
	public const    STATUS_NOT_ALLOWED = 403;

	/** @var int Requested resource not found */
	public const    STATUS_NOT_FOUND = 404;

	/** @var int Unknown JSON method */
	public const    STATUS_INVALID_METHOD = 405;

	/** @var int An error occurred */
	public const    COM_AKEEBA_CPANEL_LBL_STATUS_ERROR = 500;

	/** @var int Not implemented feature */
	public const    STATUS_NOT_IMPLEMENTED = 501;

	/** @var int Remote service not activated */
	public const    STATUS_NOT_AVAILABLE = 503;

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
			$request = [
				'encapsulation' => $rawEncapsulation,
				'body'          => $request,
			];
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
		$this->password = $request['body']['key'] ?? $this->serverKey();

		// Run the method
		$params = [];

		if (isset($request['body']['data']))
		{
			$params = (array) $request['body']['data'];
		}

		try
		{
			if (class_exists('Joomla\CMS\Component\ComponentHelper') && \Joomla\CMS\Component\ComponentHelper::isEnabled('com_akeebabackup'))
			{
				throw new \RuntimeException(sprintf('Please finish upgrading to Akeeba Backup 9 and uninstall Akeeba Backup 8 per the instructions shown on your site\'s backend, Components, Akeeba Backup'), 400);
			}

			$taskHandler = new Task($this->container);
			$data        = $taskHandler->execute($request['body']['method'], $params);
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
		$response = [
			'encapsulation' => $this->encapsulationType,
			'body'          => [
				'status' => $status,
				'data'   => null,
			],
		];

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
			$response['body']          = [
				'status' => $e->getCode(),
				'data'   => $e->getMessage(),
			];
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
