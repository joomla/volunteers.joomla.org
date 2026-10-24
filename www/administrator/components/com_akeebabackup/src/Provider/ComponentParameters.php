<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Component\AkeebaBackup\Administrator\Provider
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Provider;

defined('_JEXEC') || die;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class ComponentParameters implements ServiceProviderInterface
{
	private $defaultExtension;

	public function __construct(string $defaultExtension)
	{
		$this->defaultExtension = $defaultExtension;
	}

	public function register(Container $container)
	{
		$container->set(
			\Akeeba\Component\AkeebaBackup\Administrator\Service\ComponentParameters::class,
			function (Container $container) {
				return new \Akeeba\Component\AkeebaBackup\Administrator\Service\ComponentParameters(
					$container->get(\Akeeba\Component\AkeebaBackup\Administrator\Service\CacheCleaner::class),
					$this->defaultExtension
				);
			}
		);
	}
}