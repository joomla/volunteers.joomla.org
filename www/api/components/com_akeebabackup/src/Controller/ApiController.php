<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Api\Controller;

\defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\AkeebaBackupACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\AkeebaEngineTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\RunPluginsTrait;
use Akeeba\Component\AkeebaBackup\Api\View\Api\JsonView;
use Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\ApiController as BaseApiController;
use RuntimeException;
use Throwable;

class ApiController extends BaseApiController
{
	use AkeebaEngineTrait;
	use RunPluginsTrait;
	use AkeebaBackupACLTrait;

	/**
	 * Maps the routed controller task to the Akeeba Backup JSON API version it implements.
	 *
	 * The API version MUST NOT be derived from request input. Joomla's ApiApplication::route() distributes a route's
	 * variables — which is where a route default such as `_apiVersion` lived — into the POST input for POST requests
	 * and into the global input otherwise, and the POST input object holds a by-value copy of $_POST, so those writes
	 * never reach the global input we would read. The routed controller and task, by contrast, are set on the global
	 * input unconditionally. The dispatch target is therefore the one channel which cannot be lost, and deriving the
	 * version from it means the authentication policy cannot be changed by the client's choice of HTTP method.
	 *
	 * @since 10.4.0
	 */
	private const TASK_TO_API_VERSION = [
		'simpledisplayv2' => 2,
		'simpledisplayv3' => 3,
	];

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  9.6.0
	 */
	protected $default_view = 'api';

	/**
	 * Secret Key (cached for quicker retrieval)
	 *
	 * @var   null|string
	 * @since 7.4.0
	 */
	private ?string $key = null;

	/**
	 * The API method handler
	 *
	 * @var   Task|null
	 * @since 9.6.0
	 */
	private ?Task $taskHandler = null;

	/**
	 * The Akeeba Backup JSON API version this request was routed to.
	 *
	 * @var   int
	 * @since 10.4.0
	 */
	private int $apiVersion = 0;

	/**
	 * Was this request authorised by the Akeeba Backup Secret Word, rather than by a Joomla user?
	 *
	 * @var   bool
	 * @since 10.4.0
	 */
	private bool $isSecretWordAuthorised = false;

	/**
	 * @inheritDoc
	 */
	public function execute($task)
	{
		$routedTask = strtolower(trim((string) $task));

		// Fail closed: if we cannot tell which API version we were routed to we cannot pick an authentication policy.
		if (!isset(self::TASK_TO_API_VERSION[$routedTask]))
		{
			throw new RuntimeException('Access denied', 403);
		}

		$this->apiVersion = self::TASK_TO_API_VERSION[$routedTask];

		return parent::execute($this->apiVersion === 2 ? 'simpleDisplayV2' : 'simpleDisplayV3');
	}

	/**
	 * Handles a JSON API request routed to the legacy v2 routes
	 *
	 * @return  $this
	 * @throws  \Exception
	 * @since   10.4.0
	 *
	 * @deprecated 11.0 Use the v3 API instead.
	 * @noinspection PhpUnused
	 */
	public function simpleDisplayV2(): self
	{
		return $this->handleRequest();
	}

	/**
	 * Handles a JSON API request routed to the v3 route
	 *
	 * @return  $this
	 * @throws  \Exception
	 * @since   10.4.0
	 *
	 * @noinspection PhpUnused
	 */
	public function simpleDisplayV3(): self
	{
		return $this->handleRequest();
	}

	/**
	 * Get the request data as an array
	 *
	 * @return  array
	 * @since   9.6.0
	 */
	protected function getRequestData(): array
	{
		switch ($this->input->getMethod() ?? 'GET')
		{
			case 'GET':
				$input = $this->input->get;
				break;

			default:
			case 'POST':
				$input = $this->input->post;
				break;
		}

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

		return $data;
	}

	/**
	 * Get the BackupApi view object
	 *
	 * @return  JsonView
	 * @since   9.6.0
	 */
	protected function getMyView(): JsonView
	{
		static $view;

		if (empty($view))
		{
			$viewType   = $this->app->getDocument()->getType();
			$viewName   = $this->input->get('view', $this->default_view);
			$viewLayout = $this->input->get('layout', 'default', 'string');

			try
			{
				$view = $this->getView(
					$viewName,
					$viewType,
					'',
					['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]
				);
			}
			catch (\Exception $e)
			{
				throw new RuntimeException($e->getMessage());
			}

			$view->document = $this->app->getDocument();
		}

		return $view;
	}

	/**
	 * Handles a JSON API request, whichever API version it was routed to
	 *
	 * @return  $this
	 * @throws  \Exception
	 * @since   10.4.0
	 */
	private function handleRequest(): self
	{
		$view = $this->getMyView();

		try
		{
			$this->akeebaEngineInitialisation();
			$this->authenticate();

			$method            = $this->getRoutedVariable('method');
			$data              = $this->getRequestData();
			$this->taskHandler = Task::getInstance($this->factory);

			$this->authorise($method);

			$view->data = $this->taskHandler->execute($method, $data);
		}
		catch (Throwable $e)
		{
			$view->data = $e;
		}

		$view->display();

		return $this;
	}

	/**
	 * Reads a variable which may have been set by the API router
	 *
	 * Joomla's ApiApplication::route() writes a matched route's variables — both its defaults and its matched path
	 * segments, such as the v3 route's `method` — into the POST input for POST requests and into the global input for
	 * everything else. The POST input object is constructed with a by-value copy of $_POST, so a write through it is
	 * invisible to the global input. Reading the global input alone therefore loses route variables on POST, which is
	 * why a POST to the v3 route used to fail to resolve its method. We look in both places.
	 *
	 * @param   string  $name     The variable to read
	 * @param   mixed   $default  Returned when the variable is present in neither input
	 * @param   string  $filter   The input filter to apply
	 *
	 * @return  mixed
	 * @since   10.4.0
	 */
	private function getRoutedVariable(string $name, $default = null, string $filter = 'cmd')
	{
		$value = $this->input->get($name, null, $filter);

		if ($value === null && $this->input->getMethod() === 'POST')
		{
			$value = $this->input->post->get($name, null, $filter);
		}

		return $value ?? $default;
	}

	/**
	 * Returns the Akeeba Backup Secret Word presented with this request, if any
	 *
	 * There are two sources:
	 * 1. the X-Akeeba-Auth header (preferred, overrides all others)
	 * 2. the _akeebaAuth GET parameter
	 *
	 * @return  string|null  NULL when no Secret Word was presented at all
	 * @since   10.4.0
	 */
	private function getPresentedSecretWord(): ?string
	{
		$authSource = $this->input->server->getString('HTTP_X_AKEEBA_AUTH', null);

		if (is_null($authSource))
		{
			$authSource = $this->input->get->getString('_akeebaAuth', null);
		}

		if (!is_string($authSource) || trim($authSource) === '')
		{
			return null;
		}

		return $authSource;
	}

	/**
	 * Verifies the Akeeba Backup Secret Word presented with this request
	 *
	 * @param   string  $presentedKey  The Secret Word presented by the client
	 *
	 * @return  bool
	 * @since   7.4.0
	 */
	private function verifyKey(string $presentedKey): bool
	{
		// Is the key secure enough?
		$validKey = $this->serverKey();

		if (empty($validKey) || empty(trim($validKey)) || !Complexify::isStrongEnough($validKey, false))
		{
			return false;
		}

		return hash_equals($validKey, $presentedKey);
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

	/**
	 * Initialises the Akeeba Engine
	 *
	 * @return  void
	 * @since   9.6.0
	 */
	private function akeebaEngineInitialisation(): void
	{
		$this->loadAkeebaEngine();
		Platform::getInstance()->load_version_defines();

		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			define('AKEEBA_BACKUP_ORIGIN', 'json');
		}

		/**
		 * Let the backup tasks tell which JSON API version asked for a backup, so that the v2 API can warn about its
		 * own deprecation from inside the backup itself.
		 */
		if (!defined('AKEEBA_BACKUP_JSON_API_VERSION'))
		{
			define('AKEEBA_BACKUP_JSON_API_VERSION', $this->apiVersion);
		}
	}

	/**
	 * Performs authentication
	 *
	 * There are two mutually exclusive authentication modes, selected by whether the client presented an Akeeba Backup
	 * Secret Word. They MUST remain mutually exclusive. When the two were evaluated in sequence a request which had
	 * already satisfied the strict Joomla API login went on to satisfy the second, weaker gate as well, so neither
	 * gate was decisive and no authorisation of any kind took place.
	 *
	 * @return  void
	 * @throws  RuntimeException  When authentication fails
	 * @since   9.6.0
	 */
	private function authenticate(): void
	{
		$presentedKey = $this->getPresentedSecretWord();

		/**
		 * Mode A. The client presented an Akeeba Backup Secret Word.
		 *
		 * Possession of the Secret Word is treated as authentication AND authorisation: the bearer is granted every
		 * API method, with no Joomla user involved at all. This is the v2 access model, kept as a fallback.
		 *
		 * Note that we branch on the Secret Word being *presented*, not on it being *correct*. A wrong Secret Word is
		 * a hard failure; it must never fall through to Mode B, or a bad key would be indistinguishable from no key.
		 *
		 * @deprecated 11.0 Authenticate with a Joomla API token instead.
		 */
		if ($presentedKey !== null)
		{
			/**
			 * The "Enable JSON API" option in the component's Options gates this mode, and only this mode.
			 *
			 * It is the switch for Akeeba Backup's own, non-Joomla credential: the Secret Word. Mode B is a Joomla API
			 * token, i.e. a Joomla identity authenticated by Joomla itself, and it is gated the Joomla way: by
			 * publishing or unpublishing the "Web Services - Akeeba Backup" plugin, which is what registers the API
			 * routes in the first place. Do not promote this option into a master switch over both modes. It would take
			 * down every Joomla-idiomatic API consumer the moment an operator switched off what the option's own
			 * description presents to them as the Secret Word feature.
			 */
			if (ComponentHelper::getParams('com_akeebabackup')->get('jsonapi_enabled', 0) != 1)
			{
				throw new RuntimeException('Access denied', 503);
			}

			if (!$this->verifyKey($presentedKey))
			{
				throw new RuntimeException('Access denied', 503);
			}

			$this->isSecretWordAuthorised = true;

			return;
		}

		/**
		 * Mode B. No Secret Word: authenticate the Joomla user holding a Joomla API token.
		 *
		 * Unlike Mode A this establishes an identity, not a blanket grant. Which API methods that identity may call is
		 * decided separately, in authorise().
		 *
		 * Up to and including Joomla 5.3 only Super Users could hold an API token, which made this an implicit
		 * authorisation check as well. Joomla 5.4 allows non-Super-Users to hold API tokens, so the authorisation has
		 * to be performed explicitly.
		 */
		if (!$this->app->login(['username' => ''], ['silent' => true, 'action' => 'core.login.api']))
		{
			throw new RuntimeException('Access denied', 503);
		}
	}

	/**
	 * Performs authorisation for the API method being called
	 *
	 * @param   string|null  $method  The API method the client asked for
	 *
	 * @return  void
	 * @throws  RuntimeException  When the method is unknown, or the user is not authorised to call it
	 * @since   10.4.0
	 */
	private function authorise(?string $method): void
	{
		/**
		 * Mode A: the Secret Word is a blanket grant covering every method, and there is no Joomla user whose
		 * privileges we could check. Authorisation happened when the Secret Word was verified.
		 */
		if ($this->isSecretWordAuthorised)
		{
			return;
		}

		// Mode B: the logged in Joomla user needs the privilege its equivalent backend operation requires.
		$aclKey = $this->taskHandler->getAclKeyForMethod((string) $method);

		[$view, $task] = array_pad(explode('.', $aclKey, 2), 2, null);

		$this->akeebaBackupACLCheck($view, $task);
	}
}
