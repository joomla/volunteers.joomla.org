<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Util;

defined('AKEEBAENGINE') || die();

use Throwable;

trait FileCloseAware
{
	protected function conditionalFileClose($fp): bool
	{
		if (!is_resource($fp))
		{
			return false;
		}

		try
		{
			return @fclose($fp);
		}
		catch (Throwable $e)
		{
			return false;
		}
	}

}