<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
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
