<?php
/**
 * @package     Joomla.Legacy
 * @subpackage  Log
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

JLog::add('LogException is deprecated, use SPL Exceptions instead.', JLog::WARNING, 'deprecated');

/**
 * Exception class definition for the Log subpackage.
 *
 * @since       1.7
 * @deprecated  2.5.5 Use semantic exceptions instead
 */
class LogException extends RuntimeException
{
}
