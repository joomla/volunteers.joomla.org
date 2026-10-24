<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Mixin;

// Protect from unauthorized access
use Akeeba\Engine\Platform;

defined('_JEXEC') || die();

/**
 * Provides the method to set the current backup profile from the request variables
 */
trait ControllerActivateProfileTrait
{
	/**
	 * Set the active profile from the input parameters
	 */
	protected function setProfile()
	{
		$profile = $this->input->getInt('profile', 1);
		$profile = max(1, $profile);

		$this->app->getSession()->set('akeebabackup.profile', $profile);

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
