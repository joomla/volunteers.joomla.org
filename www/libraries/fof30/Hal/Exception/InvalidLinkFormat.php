<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Hal\Exception;

use Exception;

defined('_JEXEC') or die;

class InvalidLinkFormat extends \RuntimeException
{
	public function __construct($message = '', $code = 500, Exception $previous = null)
	{
		if (empty($message))
		{
			$message = \JText::_('LIB_FOF_HAL_ERR_INVALIDLINK');
		}

		parent::__construct($message, $code, $previous);
	}
}
