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
use Akeeba\Component\AkeebaBackup\Site\Model\OAuth2\OAuth2Exception;
use Akeeba\Component\AkeebaBackup\Site\Model\OAuth2\OAuth2UriException;
use Akeeba\Component\AkeebaBackup\Site\Model\OAuth2\ProviderInterface;
use Akeeba\Component\AkeebaBackup\Site\Model\Oauth2Model;
use Akeeba\Component\AkeebaBackup\Site\View\Oauth2\RawView;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

/**
 * Custom OAuth2 Helper controller
 *
 * @since  9.9.1
 */
class Oauth2Controller extends BaseController
{
	use ControllerEventsTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	/** @inheritDoc */
	public function __construct(
		$config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('step1');
	}

	/**
	 * Handle the first step of authentication: open the consent page
	 *
	 * @return  void
	 * @since   9.9.1
	 */
	public function step1(): void
	{
		$document   = $this->app->getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');

		/** @var RawView $view */
		$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout]);

		$view->setLayout('step1');
		$view->provider = $this->getProvider();
		$view->step1url = $view->provider->getAuthenticationUrl();

		$this->display();
	}

	/**
	 * Handle the second step of authentication: exchange the code for a set of tokens
	 *
	 * @return  void
	 * @since   9.9.1
	 */
	public function step2(): void
	{
		$document   = $this->app->getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');

		/** @var RawView $view */
		$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout]);

		$provider = $this->getProvider();

		$view->provider = $provider;

		try
		{
			$view->tokens = $provider->handleResponse($this->input);
			$view->setLayout('default');
		}
		catch (OAuth2Exception $e)
		{
			$view->exception = $e;
			$view->setLayout('error');
		}
		catch (OAuth2UriException $e)
		{
			$this->app->redirect($e->getUrl());
		}

		$this->display(false);
	}

	/**
	 * Handle exchanging a refresh token for a new set of tokens
	 *
	 * @return  void
	 * @since   9.9.1
	 */
	public function refresh(): void
	{
		$provider = $this->getProvider();

		try
		{
			$tokens = $provider->handleRefresh($this->input);

			$ret = [
				'access_token'        => $tokens['accessToken'],
				'refresh_token'     => $tokens['refreshToken'],
				'error'             => null,
				'error_description' => null,
				'error_url'         => null,
			];
		}
		catch (OAuth2Exception $e)
		{
			$ret = [
				'access_token'      => null,
				'refresh_token'     => null,
				'error'             => 'error',
				'error_description' => $e->getMessage(),
				'error_url'         => null,
			];
		}
		catch (OAuth2UriException $e)
		{
			$ret = [
				'access_token'      => null,
				'refresh_token'     => null,
				'error'             => 'error',
				'error_description' => $e->getMessage(),
				'error_url'         => $e->getUrl(),
			];
		}

		@ob_end_clean();

		header("Expires: Wed, 17 Aug 2005 00:00:00 GMT", true);
		header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0", true);
		header("Pragma: no-cache", true);

		header('Content-type: application/json');
		header('Connection: close');

		echo json_encode($ret);

		$this->app->close(200);
	}

	/**
	 * Returns the OAuth2 helper provider for the requested engine
	 *
	 * @return  ProviderInterface
	 * @since   9.9.1
	 */
	protected function getProvider(): ProviderInterface
	{
		$engine = $this->input->get->getCmd('engine');

		if (empty($engine))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var Oauth2Model $model */
		$model = $this->getModel();

		if (!$model->isEnabled($engine))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		try
		{
			return $model->getProvider($engine);
		}
		catch (\InvalidArgumentException $e)
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}