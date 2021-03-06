<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Toolbar\Exception;

defined('_JEXEC') || die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Language\Text;

class UnknownButtonType extends InvalidArgumentException
{
	public function __construct($buttonType, $code = 500, Exception $previous = null)
	{
		$message = Text::sprintf('LIB_FOF_TOOLBAR_ERR_UNKNOWNBUTTONTYPE', $buttonType);

		parent::__construct($message, $code, $previous);
	}
}
