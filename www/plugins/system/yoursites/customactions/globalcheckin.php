<?php

/**
 * @version    CVS: 1.49.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-2023 GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Access\Access;
use Joomla\CMS\MVC\Factory\LegacyFactory;

defined('_JEXEC') or die;

class YstsGlobalcheckin
{
	public static $actionname = "Global Checkin";

	public static function executeAction(&$returnData, $requestObject = null)
	{
		try
		{
			// Get the Joomla! com_checkin model
			$model = JModelLegacy::getInstance("Checkin", "CheckinModel");
			if (!$model)
			{
				if (version_compare(JVERSION, '4.0.0', "lt"))
				{
					include_once JPATH_ADMINISTRATOR . '/components/com_checkin/models/checkin.php';
					$model = new CheckinModelCheckin();
				}
				else
				{

					$LegacyFactory = new LegacyFactory;
					JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_checkin/Model");
					$model         = $LegacyFactory->createModel('Checkin', 'CheckinModel', array('ignore_request' => true));
				}

				if (!$model)
				{
					$returnData->error           = 1;
					$returnData->result          = "No Checkin Model available";
				}
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
