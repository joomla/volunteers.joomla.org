<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Exception;

use Exception;
use RuntimeException;

defined('_JEXEC') or die;

class TransparentAuthenticationNotFound extends RuntimeException
{
	public function __construct( $taClass, $code = 500, Exception $previous = null )
	{
		$message = \JText::sprintf('LIB_FOF_TRANSPARENTAUTH_ERR_NOT_FOUND', $taClass);

		parent::__construct( $message, $code, $previous );
	}

}
