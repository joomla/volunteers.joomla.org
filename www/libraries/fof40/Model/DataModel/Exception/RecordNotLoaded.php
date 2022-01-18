<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace  FOF40\Model\DataModel\Exception;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Language\Text;

class RecordNotLoaded extends BaseException
{
	public function __construct( $message = "", $code = 404, Exception $previous = null )
	{
		if (empty($message))
		{
			$message = Text::_('LIB_FOF40_MODEL_ERR_COULDNOTLOAD');
		}

		parent::__construct( $message, $code, $previous );
	}

}
