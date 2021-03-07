<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace  FOF40\View\Engine;

defined('_JEXEC') || die;

use FOF40\View\View;

/**
 * View engine for compiling PHP template files.
 */
class BladeEngine extends CompilingEngine implements EngineInterface
{
	public function __construct(View $view)
	{
		parent::__construct($view);

		// Assign the Blade compiler to this engine
		$this->compiler = $view->getContainer()->blade;
	}
}
