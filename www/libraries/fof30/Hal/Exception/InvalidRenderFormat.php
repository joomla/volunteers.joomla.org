<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Hal\Exception;

use Exception;

defined('_JEXEC') or die;

class InvalidRenderFormat extends \RuntimeException
{
	public function __construct($format, $code = 500, Exception $previous = null)
	{
		$message = \JText::sprintf('LIB_FOF_HAL_ERR_INVALIDRENDERFORMAT', $format);

		parent::__construct($message, $code, $previous);
	}
}
