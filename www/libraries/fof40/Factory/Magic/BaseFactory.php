<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory\Magic;

defined('_JEXEC') || die;

use FOF40\Container\Container;

abstract class BaseFactory
{
	/**
	 * @var   Container|null  The container where this factory belongs to
	 */
	protected $container;

	/**
	 * Section used to build the namespace prefix. We have to pass it since in CLI we need
	 * to force the section we're in (ie Site or Admin). {@see \FOF40\Container\Container::getNamespacePrefix() } for
	 * valid values
	 *
	 * @var   string
	 */
	protected $section = 'auto';

	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container we belong to
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @return string
	 */
	public function getSection(): string
	{
		return $this->section;
	}

	/**
	 * @param   string  $section
	 */
	public function setSection(string $section): void
	{
		$this->section = $section;
	}
}
