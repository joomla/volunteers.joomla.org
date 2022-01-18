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
 * Exception thrown when the access to the requested resource is forbidden under the current execution context
 */
class PossiblySuhosin extends RuntimeException
{
	public function __construct(string $message = "", int $code = 403, Exception $previous = null)
	{
		if (empty($message))
		{
			$message = Text::_('LIB_FOF40_VIEW_POSSIBLYSUHOSIN');
		}

		parent::__construct($message, $code, $previous);
	}

}
