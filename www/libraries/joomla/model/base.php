<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Model
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Joomla Platform Base Model Class
 *
 * @since       3.0.0
 * @deprecated  4.0 Use the default MVC library
 */
abstract class JModelBase implements JModel
{
	/**
	 * The model state.
	 *
	 * @var    Registry
	 * @since  3.0.0
	 */
	protected $state;

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry  $state  The model state.
	 *
	 * @since   3.0.0
	 */
	public function __construct(Registry $state = null)
	{
		// Setup the model.
		$this->state = isset($state) ? $state : $this->loadState();
	}

	/**
	 * Get the model state.
	 *
	 * @return  Registry  The state object.
	 *
	 * @since   3.0.0
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set the model state.
	 *
	 * @param   Registry  $state  The state object.
	 *
	 * @return  void
	 *
	 * @since   3.0.0
	 */
	public function setState(Registry $state)
	{
		$this->state = $state;
	}

	/**
	 * Load the model state.
	 *
	 * @return  Registry  The state object.
	 *
	 * @since   3.0.0
	 */
	protected function loadState()
	{
		return new Registry;
	}
}
