<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Controller\Mixin;

// Protect from unauthorized access
use Akeeba\Engine\Platform;

defined('_JEXEC') or die();

/**
 * Provides the method to set the current backup profile from the request variables
 */
trait ActivateProfile
{
	/**
	 * Set the active profile from the input parameters
	 */
	protected function setProfile()
	{
		$profile = $this->input->get('profile', 1, 'int');
		$profile = max(1, $profile);

		$this->container->platform->setSessionVar('profile', $profile, 'akeeba');

		/**
		 * DO NOT REMOVE!
		 *
		 * The Model will only try to load the configuration after nuking the factory. This causes Profile 1 to be
		 * loaded first. Then it figures out it needs to load a different profile and it does – but the protected keys
		 * are NOT replaced, meaning that certain configuration parameters are not replaced. Most notably, the chain.
		 * This causes backups to behave weirdly. So, DON'T REMOVE THIS UNLESS WE REFACTOR THE MODEL.
		 */
		Platform::getInstance()->load_configuration($profile);
	}

}
