<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace  FOF40\Model\DataModel\Exception;

defined('_JEXEC') || die;

use Exception;

abstract class TreeInvalidLftRgt extends \RuntimeException
{
	public function __construct( $message = '', $code = 500, Exception $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}

}
