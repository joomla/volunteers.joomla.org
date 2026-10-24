<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Exception;
use RuntimeException;

/**
 * Test an extra database definition
 *
 * @deprecated
 */
class TestDBConnection extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException|Exception  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		throw new \RuntimeException('This method is no longer supported by the Akeeba Remote JSON API', 501);
	}
}
