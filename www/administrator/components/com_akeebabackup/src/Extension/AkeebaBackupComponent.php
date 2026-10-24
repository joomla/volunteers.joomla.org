<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Service\ComponentParameters;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Psr\Container\ContainerInterface;

class AkeebaBackupComponent extends MVCComponent implements
	BootableExtensionInterface, CategoryServiceInterface, RouterServiceInterface
{
	use HTMLRegistryAwareTrait;
	use RouterServiceTrait;
	use CategoryServiceTrait;

	/**
	 * The container we were created with
	 *
	 * @var   Container
	 * @since 9.3.0
	 */
	private $container;

	/**
	 * Booting the extension. This is the function to set up the environment of the extension like
	 * registering new class loaders, etc.
	 *
	 * If required, some initial set up can be done from services of the container, eg.
	 * registering HTML services.
	 *
	 * @param   ContainerInterface  $container  The container
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	public function boot(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Returns the Container the extension was created with.
	 *
	 * We are going to use it wherever we are not instantiated through the extension object, e.g. fields.
	 *
	 * @return  Container
	 * @since   9.3.0
	 */
	public function getContainer(): Container
	{
		return $this->container;
	}

	/**
	 * Returns the dispatcher for the given application.
	 *
	 * @param   CMSApplicationInterface  $application  The application
	 *
	 * @return  DispatcherInterface
	 * @since   9.3.0
	 */
	public function getDispatcher(CMSApplicationInterface $application): DispatcherInterface
	{
		$dispatcher = parent::getDispatcher($application);

		if (method_exists($dispatcher, 'setDatabase'))
		{
			$dispatcher->setDatabase($this->container->get(DatabaseInterface::class));
		}

		return $dispatcher;
	}

	/**
	 * Returns the component's parameters service
	 *
	 * @return ComponentParameters
	 *
	 * @since  9.4.0
	 */
	public function getComponentParametersService(): ComponentParameters
	{
		return $this->container->get(ComponentParameters::class);
	}
}