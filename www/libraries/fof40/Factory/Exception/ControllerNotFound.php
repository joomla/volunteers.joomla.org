<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;
use RuntimeException;

class ControllerNotFound extends RuntimeException
{
	public function __construct(string $controller, int $code = 500, Exception $previous = null)
	{
		$message = Text::sprintf('LIB_FOF40_CONTROLLER_ERR_NOT_FOUND', $controller);

		parent::__construct($message, $code, $previous);
	}

}
