<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;

class Router extends RouterView
{
	use MVCFactoryAwareTrait;

	public function __construct(SiteApplication $app, AbstractMenu $menu, MVCFactory $factory,)
	{
		$this->setMVCFactory($factory);

		$apiView = new RouterViewConfiguration('api');
		$apiView->setKey('task');
		$this->registerView($apiView);

		$backupView = new RouterViewConfiguration('backup');
		$backupView->setKey('task');
		$this->registerView($backupView);

		$checkView = new RouterViewConfiguration('check');
		$checkView->setKey('task');
		$this->registerView($checkView);

		$oauth2View = new RouterViewConfiguration('oauth2');
		$oauth2View->setKey('engine');
		$this->registerView($oauth2View);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	public function build(&$query)
	{
		if (isset($query['view']) && !empty($query['view']))
		{
			$query['view'] = strtolower($query['view']);
		}

		return parent::build($query);
	}


}