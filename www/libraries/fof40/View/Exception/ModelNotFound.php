<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\View\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;
use RuntimeException;

/**
 * Exception thrown when we can't get a Controller's name
 */
class ModelNotFound extends RuntimeException
{
	public function __construct(string $path, string $viewName, int $code = 500, Exception $previous = null)
	{
		$message = Text::sprintf('LIB_FOF40_VIEW_MODELNOTINVIEW', $path, $viewName);

		parent::__construct($message, $code, $previous);
	}
}
