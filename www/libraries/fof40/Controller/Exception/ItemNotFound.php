<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Controller\Exception;

defined('_JEXEC') || die;

use RuntimeException;

/**
 * Exception thrown when we can't find the requested item in a read task
 */
class ItemNotFound extends RuntimeException
{

}
