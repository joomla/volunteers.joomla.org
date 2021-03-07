<?php

/**
 * @version    CVS: 1.15.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-2020 GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Access\Access;

defined('_JEXEC') or die;

class YstsGlobalcheckin
{
	public static $actionname = "Global Checkin";

	public static function executeAction( &$returnData )
	{
		try
		{
			// Get the Joomla! com_checkin model
			$model = JModelLegacy::getInstance("Checkin", "CheckingModel");
			if (!$model)
			{
				include_once JPATH_ADMINISTRATOR . '/components/com_checkin/models/checkin.php';
				$model = new CheckinModelCheckin();
				$items = $model->getItems();

				if (count($items))
				{
					$items = array_keys($items);
					$count = $model->checkin($items);
				}
			}
		}
		catch (Exception $e)
		{
			$returnData->error           = 1;
			$returnData->result          = "From plugin handler";
			$returnData->errormessages[] = $e->getMessage();
		}
	}

}
