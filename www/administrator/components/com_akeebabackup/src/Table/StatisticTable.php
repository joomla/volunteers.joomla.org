<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\GetPropertiesAwareTrait;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

#[\AllowDynamicProperties]
class StatisticTable extends Table
{
	use GetPropertiesAwareTrait;

	/**
	 * The absolute path to the backup archive on the local filesystem
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $absolute_path = null;

	/**
	 * Basename and extension of the backup archive (last part with .jpa, .jps or .zip extension)
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $archivename = null;

	/**
	 * Backup end date and time
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $backupend = null;

	/**
	 * The backup identifier
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $backupid = null;

	/**
	 * Backup start date and time
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $backupstart = null;

	/**
	 * Backup comment
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $comment = null;

	/**
	 * Backup record description (non-nullable)
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $description = null;

	/**
	 * Are the files still present in the local filesystem?
	 *
	 * @since 9.0.0
	 * @var int
	 */
	public $filesexist = 1;

	/**
	 * Is the backup record frozen?
	 *
	 * @since 9.0.0
	 * @var int
	 */
	public $frozen = 0;

	/**
	 * Backup record ID
	 *
	 * @since 9.0.0
	 * @var int|null
	 */
	public $id = null;

	/**
	 * Am I running a backup step?
	 *
	 * If this is 0 and the state is 'run' I have just finished running a backup step, and I'm waiting for the next
	 * step to be triggered. If this is 1 and the state is 'run' I am either executing a step or my execution has been
	 * killed by PHP or the Operating System without me being notified. If the state is other than 'run' this MUST be 0.
	 * If the state is other than 'run' and this is 1 something has gone seriously wrong!
	 *
	 * @since 9.0.0
	 * @var int
	 */
	public $instep = 0;

	/**
	 * The number of parts of the backup archive. 0 (or 1) means single part.
	 *
	 * @since 9.0.0
	 * @var int
	 */
	public $multipart = 0;

	/**
	 * Backup origin
	 *
	 * @since 9.0.0
	 * @var string
	 */
	public $origin = 'backend';

	/**
	 * Backup profile ID
	 *
	 * @since 9.0.0
	 * @var int
	 */
	public $profile_id = 1;

	/**
	 * The pseudo-URI for the absolute path of the backup archive to the remote filesystem
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $remote_filename = null;

	/**
	 * Backup status (enumerable): 'run', 'fail', 'complete'
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $status = 'run';

	/**
	 * The backup tag
	 *
	 * @since 9.0.0
	 * @var string|null
	 */
	public $tag = null;

	/**
	 * Total backup size, in bytes
	 *
	 * @since 9.0.0
	 * @var int
	 */
	public $total_size = 0;

	/**
	 * Backup type, e.g. 'full', 'dbonly', ...
	 *
	 * @since 9.0.0
	 * @var string
	 */
	public $type = 'full';

	/**
	 * Object constructor to set table and key fields.
	 *
	 * @param   DatabaseDriver  $db  DatabaseDriver object.
	 *
	 * @since   9.0.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__akeebabackup_backups', 'id', $db);

		$this->setColumnAlias('published', 'frozen');
	}
}