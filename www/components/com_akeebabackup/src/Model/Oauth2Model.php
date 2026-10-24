<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Site\Model\OAuth2\ProviderInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseModel;

/**
 * Custom OAuth2 Helper model
 *
 * @since   9.9.1
 */
class Oauth2Model extends BaseModel
{
	/**
	 * Returns the provider object for the requested engine
	 *
	 * @param   string  $engine  The requested engine
	 *
	 * @return  ProviderInterface  The provider object
	 * @throws  \InvalidArgumentException  If the engine is not available
	 * @since   9.9.1
	 */
	public function getProvider(string $engine): ProviderInterface
	{
		$className = __NAMESPACE__ . '\\OAuth2\\' . ucfirst(strtolower($engine)) . 'Engine';

		if (!class_exists($className))
		{
			throw new \InvalidArgumentException(sprintf("Invalid engine: %s", $engine));
		}

		return new $className;
	}

	/**
	 * Is the requested provider enabled in the component options?
	 *
	 * @param   string  $engine  The requested engine
	 *
	 * @return  bool
	 * @since   9.9.1
	 */
	public function isEnabled(string $engine): bool
	{
		$key     = sprintf('oauth2_client_%s', strtolower($engine));
		$cParams = ComponentHelper::getParams('com_akeebabackup');

		return $cParams->get($key, 0) != 0;
	}
}