<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;



use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Transfer\FtpCurl as TransferFtpCurl;

class Ftpcurl extends Ftp
{
	public function __construct()
	{
		parent::__construct();

		$this->engineKey = 'engine.postproc.ftpcurl.';
	}

	protected function makeConnector()
	{
		Factory::getLog()->debug(__CLASS__ . ':: Connecting to remote FTP');

		$options = $this->getConfig();

		return new TransferFtpCurl($options);
	}
}
