<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Exception;

use Exception;

defined('_JEXEC') or die;

/**
 * Class DataModelRequired
 * @package    FOF30\Form\Exception
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class DataModelRequired extends \RuntimeException
{
	public function __construct($className, $code = 0, Exception $previous = null)
	{
		$message = \JText::sprintf('LIB_FOF_FORM_ERR_DATAMODEL_REQUIRED', $className);

		parent::__construct($message, $code, $previous);
	}
}
