<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Model\DataModel\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;

class TreeRootNotFound extends \RuntimeException
{
	public function __construct($tableName, $lft, $code = 500, Exception $previous = null)
	{
		$message = Text::sprintf('LIB_FOF40_MODEL_ERR_TREE_ROOTNOTFOUND', $tableName, $lft);

		parent::__construct($message, $code, $previous);
	}

}
