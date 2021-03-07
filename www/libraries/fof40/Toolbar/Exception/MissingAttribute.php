<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Toolbar\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;

class MissingAttribute extends \InvalidArgumentException
{
	public function __construct(string $missingArgument, string $buttonType, int $code = 500, Exception $previous = null)
	{
		$message = Text::sprintf('LIB_FOF40_TOOLBAR_ERR_MISSINGARGUMENT', $missingArgument, $buttonType);

		parent::__construct($message, $code, $previous);
	}
}
