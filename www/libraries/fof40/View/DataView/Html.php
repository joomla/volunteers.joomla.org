<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\View\DataView;

defined('_JEXEC') || die;

use FOF40\Render\RenderInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;

class Html extends Raw implements DataViewInterface
{
	/** @var bool Should I set the page title in the front-end of the site? */
	public $setFrontendPageTitle = false;

	/** @var string The translation key for the default page title */
	public $defaultPageTitle;

	/**
	 * Should FEFHelpBrowse::orderheader() render the pagination (items per page) dropdown?
	 *
	 * @var   bool
	 * @since 4.0.0
	 */
	public $showBrowsePagination = true;

	/**
	 * Should FEFHelpBrowse::orderheader() render the ordering direction dropdown?
	 *
	 * @var   bool
	 * @since 4.0.0
	 */
	public $showBrowseOrdering = true;

	/**
	 * Should FEFHelpBrowse::orderheader() render the order by item dropdown?
	 *
	 * @var   bool
	 * @since 4.0.0
	 */
	public $showBrowseOrderBy = true;

	public function setPageTitle()
	{
		if (!$this->container->platform->isFrontend())
		{
			return '';
		}

		/** @var SiteApplication $app */
		$app      = JoomlaFactory::getApplication();
		$document = JoomlaFactory::getDocument();
		$menus    = $app->getMenu();
		$menu     = $menus->getActive();

		// Get the option and view name
		$option = $this->container->componentName;
		$view   = $this->getName();

		// Get the default page title translation key
		$default = empty($this->defaultPageTitle) ? $option . '_TITLE_' . $view : $this->defaultPageTitle;

		$params = $app->getParams($option);

		// Set the default value for page_heading
		$params->def('page_heading', ($menu !== null) ? $params->get('page_title', $menu->title) : Text::_($default));

		// Set the document title
		$title    = $params->get('page_title', '');
		$sitename = $app->get('sitename');

		if ($title == $sitename)
		{
			$title = Text::_($default);
		}

		if (empty($title))
		{
			$title = $sitename;
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
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

	protected function initialise(): void
	{
		$view = $this->getName();
		$task = $this->task;

		$renderer = $this->container->renderer;
		$renderer->initialise($view, $task);
	}

	/**
	 * Runs before rendering the view template, echoing HTML to put before the
	 * view template's generated HTML
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 */
	protected function preRender(): void
	{
		$view = $this->getName();
		$task = $this->task;

		// Don't load the toolbar on CLI
		$platform = $this->container->platform;

		if (!$platform->isCli())
		{
			$toolbar        = $this->container->toolbar;
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
	protected function postRender(): void
	{
		$view = $this->getName();
		$task = $this->task;

		$renderer = $this->container->renderer;

		if ($renderer instanceof RenderInterface)
		{
			$renderer->postRender($view, $task);
		}
	}

	/**
	 * Executes before rendering the page for the Add task.
	 */
	protected function onBeforeAdd()
	{
		// Hide main menu
		JoomlaFactory::getApplication()->input->set('hidemainmenu', true);

		parent::onBeforeAdd();
	}

	/**
	 * Executes before rendering the page for the Edit task.
	 */
	protected function onBeforeEdit()
	{
		// Hide main menu
		JoomlaFactory::getApplication()->input->set('hidemainmenu', true);

		parent::onBeforeEdit();
	}
} 
