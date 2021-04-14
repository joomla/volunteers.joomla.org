<?php

/**
 * @version    CVS: 1.16.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-2020 GWE Systems Ltd
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
		}
		else
		{
			$customresult->messages[] = "COM_YOURSITES_EXTRA_CHECK_SAMPLE_INCORRECT";
			$customresult->warning = 1;
			$customresult->checkinfo['key']  = "COM_YOURSITES_EXTRA_CHECK_SAMPLE_INCORRECT_DETAIL";
			$customresult->checkinfo['data'] = array($live_site);
			$customresult->checkinfo['status'] = -1;
		}

	}

}
