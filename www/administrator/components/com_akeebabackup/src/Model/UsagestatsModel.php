<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\UsageStats\Collector\Constants\SoftwareType;
use Akeeba\UsageStats\Collector\StatsCollector;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

#[\AllowDynamicProperties]
class UsagestatsModel extends BaseDatabaseModel
{
	/**
	 * Send site information to the remove collection service
	 *
	 * @return  bool
	 */
	public function collectStatistics()
	{
		$params = ComponentHelper::getParams('com_akeebabackup');

		// Is data collection turned off?
		if (!$params->get('stats_enabled', 1))
		{
			return false;
		}

		// Make sure the autoloader for our Composer dependencies is loaded.
		if (!class_exists(StatsCollector::class))
		{
			try
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';
			}
			catch (\Throwable $e)
			{
				return false;
			}
		}
		// Usage stats collection class is undefined, we cannot continue
		if (!class_exists(StatsCollector::class, false))
		{
			return false;
		}

		if (!defined('AKEEBABACKUP_VERSION'))
		{
			@include_once __DIR__ . '/../../version.php';
		}

		if (!defined('AKEEBABACKUP_VERSION'))
		{
			define('AKEEBABACKUP_VERSION', 'dev');
			define('AKEEBABACKUP_DATE', date('Y-m-d'));
		}

		try
		{
			(new StatsCollector(
				SoftwareType::AB_JOOMLA_CORE,
				AKEEBABACKUP_VERSION,
				defined('AKEEBABACKUP_PRO') ? AKEEBABACKUP_PRO : false
			))->conditionalSendStatistics();
		}
		catch (\Throwable $e)
		{
			return false;
		}

		return true;
	}
}