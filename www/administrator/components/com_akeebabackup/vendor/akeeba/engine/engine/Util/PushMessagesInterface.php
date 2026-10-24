<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

/**
 * @package     Akeeba\Engine\Util
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Engine\Util;

defined('AKEEBAENGINE') || die();

interface PushMessagesInterface
{
	/**
	 * Sends a push message to all connected devices. The intent is to provide the user with an information message,
	 * e.g. notify them about the progress of the backup.
	 *
	 * @param   string  $subject  The subject of the message, shown in the lock screen. Keep it short.
	 * @param   string  $details  Long(er) description of what the message is about. Plain text (no HTML).
	 *
	 * @return  void
	 */
	public function message($subject, $details = null);

	/**
	 * Sends a push message, containing a URL/URI, to all connected devices. The URL will be rendered as something
	 * clickable on most devices.
	 *
	 * @param   string  $url      The URL/URI
	 * @param   string  $subject  The subject of the message, shown in the lock screen. Keep it short.
	 * @param   string  $details  Long(er) description of what the message is about. Plain text (no HTML).
	 *
	 * @return  void
	 */
	public function link($url, $subject, $details = null);
}