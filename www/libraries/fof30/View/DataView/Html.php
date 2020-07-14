<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\View\DataView;

use FOF30\Render\RenderInterface;

defined('_JEXEC') or die;

class Html extends Raw implements DataViewInterface
{
	/** @var bool Should I set the page title in the front-end of the site? */
	public $setFrontendPageTitle = false;

	/** @var string The translation key for the default page title */
	public $defaultPageTitle = null;

	/**
	 * Runs before rendering the view template, echoing HTML to put before the
	 * view template's generated HTML
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 */
	protected function preRender()
	{
		$view = $this->getName();
		$task = $this->task;

		// Don't load the toolbar on CLI
		$platform = $this->container->platform;

		if (!$platform->isCli())
		{
			$toolbar = $this->container->toolbar;
			$toolbar->perms = $this->permissions;
			$toolbar->renderToolbar($view, $task);
		}

		if ($platform->isFrontend() && $this->setFrontendPageTitle)
		{
			$this->setPageTitle();
		}

		$renderer = $this->container->renderer;
		$renderer->preRender($view, $task);
	}

	/**
	 * Runs after rendering the view template, echoing HTML to put after the
	 * view template's generated HTML
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 */
	protected function postRender()
	{
		$view = $this->getName();
		$task = $this->task;

		$renderer = $this->container->renderer;

		if ($renderer instanceof RenderInterface)
		{
			$renderer->postRender($view, $task);
		}
	}

	public function setPageTitle()
	{
		if (!$this->container->platform->isFrontend())
		{
			return '';
		}

		/** @var \JApplicationSite $app */
		$app = \JFactory::getApplication();
		$document = \JFactory::getDocument();
		$menus = $app->getMenu();
		$menu = $menus->getActive();
		$title = null;

		// Get the option and view name
		$option = $this->container->componentName;
		$view = $this->getName();

		// Get the default page title translation key
		$default = empty($this->defaultPageTitle) ? $option . '_TITLE_' . $view : $this->defaultPageTitle;

		$params = $app->getParams($option);

		// Set the default value for page_heading
		if ($menu)
		{
			$params->def('page_heading', $params->get('page_title', $menu->title));
		}
		else
		{
			$params->def('page_heading', \JText::_($default));
		}

		// Set the document title
		$title = $params->get('page_title', '');
		$sitename = $app->get('sitename');

		if ($title == $sitename)
		{
			$title = \JText::_($default);
		}

		if (empty($title))
		{
			$title = $sitename;
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = \JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = \JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$document->setTitle($title);

		// Set meta
		if ($params->get('menu-meta_description'))
		{
			$document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $params->get('menu-meta_keywords'));
		}

		if ($params->get('robots'))
		{
			$document->setMetadata('robots', $params->get('robots'));
		}

		return $title;
	}

	/**
	 * Executes before rendering the page for the Add task.
	 */
	protected function onBeforeAdd()
	{
		// Hide main menu
		\JFactory::getApplication()->input->set('hidemainmenu', true);

		parent::onBeforeAdd();
	}

	/**
	 * Executes before rendering the page for the Edit task.
	 */
	protected function onBeforeEdit()
	{
		// Hide main menu
		\JFactory::getApplication()->input->set('hidemainmenu', true);

		parent::onBeforeEdit();
	}
} 
