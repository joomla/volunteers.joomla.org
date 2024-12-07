<?php

/**
 * @version    CVS: 1.49.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-YOURSITES_COPYRIGHT GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Access\Access;

defined('_JEXEC') or die;

class YstsSampleChecks
{
	public static $checkname = "Sample Check";

	public static function performCheck( & $customresult)
	{
		$customresult->checkinfo['data'] = array();

		$live_site = JFactory::getConfig()->get('live_site', '');

		if (empty($live_site))
		{
			$customresult->messages[] = "COM_YOURSITES_EXTRA_CHECK_SAMPLE_CORRECT";
			$customresult->checkinfo['key']  = "COM_YOURSITES_EXTRA_CHECK_SAMPLE_CORRECT_DETAIL";
			$customresult->checkinfo['data'] = array($live_site);
			$customresult->checkinfo['status'] = 1;
			$customresult->valid = 1;
		}
		else
		{
			$customresult->messages[] = "COM_YOURSITES_EXTRA_CHECK_SAMPLE_INCORRECT";
			$customresult->warning = 1;
			$customresult->checkinfo['key']  = "COM_YOURSITES_EXTRA_CHECK_SAMPLE_INCORRECT_DETAIL";
			$customresult->checkinfo['data'] = array($live_site);
			$customresult->checkinfo['status'] = -1;
			$customresult->valid = 0;
		}

	}

}
