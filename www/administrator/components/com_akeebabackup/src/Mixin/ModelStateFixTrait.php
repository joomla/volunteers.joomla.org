<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Component\AkeebaBackup\Administrator\Model\Mixin
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die;

trait ModelStateFixTrait
{
	/**
	 * Set the __state_set flag.
	 *
	 * Calling setState on a model does NOT set the __state_set flag. Next time you call getState the model will always
	 * go through populateState(). However, Joomla's default populateState for ListModel and AdminModel tries to call
	 * the getUserStateFromRequest method against the application object **without** checking if this method exists.
	 * This method does not, in fact, exist in the Console application — it only exists in the site, administrator and
	 * cli applications. As a result trying to use a model in the Console application breaks.
	 *
	 * The solution is this one–line method which sets the __state_set flag, preventing Joomla from sabotaging itself.
	 *
	 * The funny thing is that this problem did not occur on Joomla 4.0 and earlier. Well done, guys, you've broken
	 * Joomla yet again by not stopping to think that there's more to Joomla than the HTML applications. Nothing says
	 * “world–class maintenance team” than silly blunders like this.
	 *
	 * @param   bool  $flag  The state of the __state_set flag to apply. Default: true.
	 *
	 * @return  void
	 * @since   9.3.0
	 */
	public function setStateSetFlag(bool $flag = true): void
	{
		$this->__state_set = $flag;
	}
}