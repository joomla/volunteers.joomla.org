<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter;

defined('AKEEBAENGINE') || die();

/**
 * Subdirectories exclusion filter
 */
class Skipdirs extends Base
{
	public function __construct()
	{
		$this->object  = 'dir';
		$this->subtype = 'children';
		$this->method  = 'direct';

		if (empty($this->filter_name))
		{
			$this->filter_name = strtolower(basename(__FILE__, '.php'));
		}

		parent::__construct();
	}
}
