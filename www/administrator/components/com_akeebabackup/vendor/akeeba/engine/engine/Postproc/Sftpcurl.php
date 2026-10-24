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

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Transfer\SftpCurl as SftpTransferCurl;
use RuntimeException;

class Sftpcurl extends Sftp
{
	public function __construct()
	{
		parent::__construct();

		$this->engineKey = 'engine.postproc.sftpcurl.';
	}

	protected function makeConnector()
	{
		Factory::getLog()->debug(__CLASS__ . ':: Connecting to remote SFTP');

		$options    = $this->getConfig();
		$sftphandle = new SftpTransferCurl($options);

		if (!$this->sftp_chdir($options['directory'], $sftphandle))
		{
			throw new RuntimeException(sprintf(
				"Invalid initial directory %s for the remote SFTP server",
				$options['directory']
			));
		}

		return $sftphandle;
	}
}
