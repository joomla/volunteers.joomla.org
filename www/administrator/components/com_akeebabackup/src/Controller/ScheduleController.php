<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Engine\Util\RandomValue;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;

class ScheduleController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;

	public function enableFrontend(bool $cachable = false, array $urlparams = [])
	{
		// CSRF prevention
		$this->checkToken('request');

		$params = ComponentHelper::getParams('com_akeebabackup');

		$params->set('legacyapi_enabled', 1);

		$secretWord = $params->get('frontend_secret_word', null);

		if (empty($secretWord))
		{
			$random    = new RandomValue();
			$newSecret = $random->generateString(32);
			$params->set('frontend_secret_word', $newSecret);
		}

		$this->app->bootComponent('com_akeebabackup')
			->getComponentParametersService()
			->save($params);

		$url = Uri::base() . 'index.php?option=com_akeebabackup&view=Schedule';

		$this->setRedirect($url);
	}

	public function enableJsonApi(bool $cachable = false, array $urlparams = [])
	{
		// CSRF prevention
		$this->checkToken('request');

		$params = ComponentHelper::getParams('com_akeebabackup');

		$params->set('jsonapi_enabled', 1);

		$secretWord = $params->get('frontend_secret_word', null);

		if (empty($secretWord))
		{
			$random    = new RandomValue();
			$newSecret = $random->generateString(32);
			$params->set('frontend_secret_word', $newSecret);
		}

		$this->app->bootComponent('com_akeebabackup')
			->getComponentParametersService()
			->save($params);

		$url = Uri::base() . 'index.php?option=com_akeebabackup&view=Schedule';

		$this->setRedirect($url);
	}

	public function resetSecretWord(bool $cachable = false, array $urlparams = []): void
	{
		// CSRF prevention
		$this->checkToken('request');

		$newSecret = JoomlaFactory::getApplication()->getSession()->get('akeebabackup.cpanel.newSecretWord', null);

		if (empty($newSecret))
		{
			$random    = new RandomValue();
			$newSecret = $random->generateString(32);
			JoomlaFactory::getApplication()->getSession()->set('akeebabackup.cpanel.newSecretWord', $newSecret);
		}

		$params = ComponentHelper::getParams('com_akeebabackup');

		$params->set('frontend_secret_word', $newSecret);

		$this->app->bootComponent('com_akeebabackup')
			->getComponentParametersService()
			->save($params);

		JoomlaFactory::getApplication()->getSession()->set('akeebabackup.cpanel.newSecretWord', null);

		$url = Uri::base() . 'index.php?option=com_akeebabackup&view=Schedule';

		$this->setRedirect($url);
	}
}