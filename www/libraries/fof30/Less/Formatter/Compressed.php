<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Less\Formatter;

defined('_JEXEC') or die;

/**
 * This class is taken verbatim from:
 *
 * lessphp v0.3.9
 * http://leafo.net/lessphp
 *
 * LESS css compiler, adapted from http://lesscss.org
 *
 * Copyright 2012, Leaf Corcoran <leafot@gmail.com>
 * Licensed under MIT or GPLv3, see LICENSE
 *
 * @since    2.0
 */
class Compressed extends Classic
{
	public $disableSingle = true;

	public $open = "{";

	public $selectorSeparator = ",";

	public $assignSeparator = ":";

	public $break = "";

	public $compressColors = true;

	/**
	 * Indent a string by $n positions
	 *
	 * @param   integer  $n  How many positions to indent
	 *
	 * @return  string  The indented string
	 */
	public function indentStr($n = 0)
	{
		return "";
	}
}
