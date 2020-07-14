<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Controller\Mixin;

// Protect from unauthorized access
use Akeeba\Engine\Platform;

defined('_JEXEC') or die();

/**
 * Provides the method to send custom HTTP redirection headers
 */
trait CustomRedirection
{
	/**
	 * Sends custom HTTP redirection headers
	 *
	 * @param   string  $url     The URL to redirect to
	 * @param   string  $header  The HTTP header to send, default 302 Found
	 */
	protected function customRedirect($url, $header = '302 Found')
	{
		header('HTTP/1.1 ' . $header);
		header('Location: ' . $url);
		header('Content-Type: text/plain');
		header('Connection: close');

		$this->container->platform->closeApplication();
	}

}
