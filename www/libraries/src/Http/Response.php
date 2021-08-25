<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Http;

defined('JPATH_PLATFORM') or die;

/**
 * HTTP response data object class.
 *
 * @since  1.7.3
 */
class Response
{
	/**
	 * @var    integer  The server response code.
	 * @since  1.7.3
	 */
	public $code;

	/**
	 * @var    array  Response headers.
	 * @since  1.7.3
	 */
	public $headers = array();

	/**
	 * @var    string  Server response body.
	 * @since  1.7.3
	 */
	public $body;
}
