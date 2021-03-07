<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace  FOF40\Model\DataModel\Filter\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;

class InvalidFieldObject extends \InvalidArgumentException
{
	public function __construct( $message = "", $code = 500, Exception $previous = null )
	{
		if (empty($message))
		{
			$message = Text::_('LIB_FOF40_MODEL_ERR_FILTER_INVALIDFIELD');
		}

		parent::__construct( $message, $code, $previous );
	}

}
