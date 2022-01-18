<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\View\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;
use RuntimeException;

/**
 * Exception thrown when we are trying to operate on an empty section stack
 */
class EmptyStack extends RuntimeException
{
	public function __construct(string $message = "", int $code = 500, Exception $previous = null)
	{
		$message = Text::_('LIB_FOF40_VIEW_EMPTYSECTIONSTACK');

		parent::__construct($message, $code, $previous);
	}
}
