<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

/**
 * Get the version information of Akeeba Backup
 */
class GetVersion extends AbstractTask
{
	/**
	 * The backend view.task key this API method is equivalent to.
	 *
	 * @var    string
	 * @since  10.4.0
	 */
	protected $aclKey = 'controlpanel';

	/**
	 * Execute the JSON API task
	 *
	 * @param   array $parameters The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		$edition = AKEEBABACKUP_PRO ? 'pro' : 'core';

		return (object)[
			'api'        => 600,
			'component'  => AKEEBABACKUP_VERSION,
			'date'       => AKEEBABACKUP_DATE,
			'edition'    => $edition
		];
	}
}
