<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Model\DataModel\Exception;

use Exception;

defined('_JEXEC') or die;

class CannotLockNotLoadedRecord extends BaseException
{
	public function __construct( $message = '', $code = 500, Exception $previous = null )
	{
		if (empty($message))
		{
			$message = \JText::_('LIB_FOF_MODEL_ERR_CANNOTLOCKNOTLOADEDRECORD');
		}

		parent::__construct( $message, $code, $previous );
	}

}
