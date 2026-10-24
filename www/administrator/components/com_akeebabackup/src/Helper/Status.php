<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

class Status
{
	/**
	 * Are we ready to take a new backup?
	 *
	 * @var  bool
	 */
	public $status = false;

	/**
	 * The detected warnings
	 *
	 * @var  array
	 */
	protected $warnings = [];

	/**
	 * Public constructor.
	 *
	 * Automatically initializes the object with the status and warnings.
	 */
	public function __construct()
	{
		$this->status   = Factory::getConfigurationChecks()->getShortStatus();
		$this->warnings = Factory::getConfigurationChecks()->getDetailedStatus();
	}

	/**
	 * Get a Singleton instance
	 *
	 * @return  self
	 */
	public static function &getInstance()
	{
		static $instance = null;

		if (empty($instance))
		{
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Returns the HTML for the backup status cell
	 *
	 * @return  string  HTML
	 */
	public function getStatusCell()
	{
		$status = Factory::getConfigurationChecks()->getShortStatus();
		$quirks = Factory::getConfigurationChecks()->getDetailedStatus();

		if ($status && empty($quirks))
		{
			$html = '<div class="alert alert-success" role="alert"><p>' . Text::_('COM_AKEEBABACKUP_CPANEL_LBL_STATUS_OK') . '</p></div>';
		}
		elseif ($status && !empty($quirks))
		{
			$html = '<div class="alert alert-warning" role="alert"><p>' . Text::_('COM_AKEEBABACKUP_CPANEL_LBL_STATUS_WARNING') . '</p></div>';
		}
		else
		{
			$html = '<div class="alert alert-danger" role="alert"><p>' . Text::_('COM_AKEEBABACKUP_CPANEL_LBL_STATUS_ERROR') . '</p></div>';
		}

		return $html;
	}

	/**
	 * Returns HTML for the warnings (status details)
	 *
	 * @param   bool  $onlyErrors  Should I only return errors? If false (default) errors AND warnings are returned.
	 *
	 * @return  string  HTML
	 */
	public function getQuirksCell($onlyErrors = false)
	{
		$html   = '<p>' . Text::_('COM_AKEEBABACKUP_CPANEL_WARNING_QNONE') . '</p>';
		$quirks = Factory::getConfigurationChecks()->getDetailedStatus();

		if (!empty($quirks))
		{
			$html = "<ul class=\"list-unstyled\">\n";

			foreach ($quirks as $quirk)
			{
				$html .= $this->renderWarnings($quirk, $onlyErrors);
			}

			$html .= "</ul>\n";
		}

		return $html;
	}

	/**
	 * Returns a boolean value, indicating if warnings have been detected.
	 *
	 * @return  bool  True if there is at least one detected warnings
	 */
	public function hasQuirks()
	{
		$quirks = Factory::getConfigurationChecks()->getDetailedStatus();

		return !empty($quirks);
	}

	/**
	 * Returns the details of the latest backup as HTML
	 *
	 * @return  string  HTML
	 */
	public function getLatestBackupDetails()
	{
		/** @var DatabaseDriver $db */
		$db    = JoomlaFactory::getContainer()->get(DatabaseInterface::class);
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('MAX(' . $db->qn('id') . ')')
			->from($db->qn('#__akeebabackup_backups'));
		$db->setQuery($query);
		$id = $db->loadResult();

		$backup_types = Factory::getEngineParamsProvider()->loadScripting();

		if (empty($id))
		{
			return '<p class="label">' . Text::_('COM_AKEEBABACKUP_BACKUP_STATUS_NONE') . '</p>';
		}

		$record = Platform::getInstance()->get_statistics($id);

		switch ($record['status'])
		{
			case 'run':
				$status      = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_STATUS_PENDING');
				$statusClass = "badge bg-warning";
				break;

			case 'fail':
				$status      = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_STATUS_FAIL');
				$statusClass = "badge bg-danger";
				break;

			case 'complete':
				$status      = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_STATUS_OK');
				$statusClass = "badge bg-success";
				break;

			default:
				$status      = '';
				$statusClass = '';
		}

		switch ($record['origin'])
		{
			case 'frontend':
				$origin = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN_FRONTEND');
				break;

			case 'backend':
				$origin = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN_BACKEND');
				break;

			case 'cli':
				$origin = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN_CLI');
				break;

			default:
				$origin = '&ndash;';
				break;
		}

		$type = '';

		if (array_key_exists($record['type'], $backup_types['scripts']))
		{
			$type = Platform::getInstance()->translate($backup_types['scripts'][$record['type']]['text']);
		}

		$startTime = clone JoomlaFactory::getDate($record['backupstart'], 'UTC');
		$app       = JoomlaFactory::getApplication();
		$user      = $app->getIdentity() ?? (new User());
		$tz        = new \DateTimeZone($user->getParam('timezone', $app->get('offset', 'UTC')));
		$startTime->setTimezone($tz);

		$html = '<table class="table table-striped">';
		$html .= '<tr><td>' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_START') . '</td><td>' . $startTime->format(Text::_('DATE_FORMAT_LC2'), true) . '</td></tr>';
		$html .= '<tr><td>' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_DESCRIPTION') . '</td><td>' . $record['description'] . '</td></tr>';
		$html .= '<tr><td>' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_STATUS') . '</td><td><span class="label ' . $statusClass . '">' . $status . '</span></td></tr>';
		$html .= '<tr><td>' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN') . '</td><td>' . $origin . '</td></tr>';
		$html .= '<tr><td>' . Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_TYPE') . '</td><td>' . $type . '</td></tr>';
		$html .= '</table>';

		return $html;
	}

	/**
	 * Gets the HTML for a single line of the warnings area.
	 *
	 * @param   array  $quirk       A quirk definition array
	 * @param   bool   $onlyErrors  Should I only return errors? If false (default) errors AND warnings are returned.
	 *
	 * @return  string  HTML
	 */
	private function renderWarnings($quirk, $onlyErrors = false)
	{
		if ($onlyErrors && ($quirk['severity'] != 'critical'))
		{
			return '';
		}

		switch ($quirk['severity'])
		{

			case 'critical':
				$classSuffix = 'danger';
				break;

			case 'high':
				$classSuffix = 'warning';
				break;

			case 'medium':
				$classSuffix = 'primary';
				break;

			case 'low':
			default:
				$classSuffix = 'secondary';
				break;
		}

		$quirk['severity'] = $quirk['severity'] == 'critical' ? 'high' : $quirk['severity'];

		return sprintf(
			"<li class=\"mt-2\"><a class=\"severity-%s link-%s\" href=\"%s\" target=\"_blank\">%s</a></li>\n",
			$quirk['severity'], $classSuffix, $quirk['help_url'], $quirk['description']
		);

	}

}