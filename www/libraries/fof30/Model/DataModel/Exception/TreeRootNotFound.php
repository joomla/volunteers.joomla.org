<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Model\DataModel\Exception;

use Exception;

defined('_JEXEC') or die;

class TreeRootNotFound extends \RuntimeException
{
	public function __construct( $tableName, $lft, $code = 500, Exception $previous = null )
	{
		$message = \JText::sprintf('LIB_FOF_MODEL_ERR_TREE_ROOTNOTFOUND', $tableName, $lft);

		parent::__construct( $message, $code, $previous );
	}

}
