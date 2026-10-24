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

namespace Akeeba\Engine\Platform\Exception;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Platform;
use Exception;
use RuntimeException;

/**
 * Thrown when the settings cannot be decrypted, e.g. when the server no longer has encyrption enabled or the key has
 * changed.
 */
class DecryptionException extends RuntimeException
{
	public function __construct($message = null, $code = 500, ?Exception $previous = null)
	{
		if (empty($message))
		{
			$message = Platform::getInstance()->translate('COM_AKEEBA_CONFIG_ERR_DECRYPTION');
		}

		parent::__construct($message, $code, $previous);
	}

}
