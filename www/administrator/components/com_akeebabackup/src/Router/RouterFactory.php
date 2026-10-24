<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Router;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use RuntimeException;

class RouterFactory implements RouterFactoryInterface
{
	/**
	 * THe MVC factory object
	 *
	 * @var   MVCFactoryInterface
	 * @since 5.0.0
	 */
	private $factory;

	/**
	 * The extension's namespace
	 *
	 * @var   string
	 * @since 5.0.0
	 */
	private $namespace;

	public function __construct(string $namespace, MVCFactoryInterface $factory)
	{
		$this->namespace       = $namespace;
		$this->factory         = $factory;
	}

	/** @inheritdoc */
	public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
	{
		$className = trim($this->namespace, '\\') . '\\' . ucfirst($application->getName()) . '\\Service\\Router';

		if (!class_exists($className))
		{
			throw new RuntimeException('No router available for Akeeba Backup.');
		}

		return new $className($application, $menu, $this->factory);
	}
}