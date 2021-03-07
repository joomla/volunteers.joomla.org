<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Model\DataModel\Behaviour;

defined('_JEXEC') || die;

use FOF40\Event\Observer;
use FOF40\Model\DataModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\Registry\Registry;

/**
 * FOF model behavior class to populate the state with the front-end page parameters
 *
 * @since    2.1
 */
class PageParametersToState extends Observer
{
	public function onAfterConstruct(DataModel &$model)
	{
		// This only applies to the front-end
		if (!$model->getContainer()->platform->isFrontend())
		{
			return;
		}

		// Get the page parameters
		/** @var SiteApplication $app */
		$app = JoomlaFactory::getApplication();
		/** @var Registry $params */
		$params = $app->getParams();

		// Extract the page parameter keys
		$asArray = [];

		if (is_object($params) && method_exists($params, 'toArray'))
		{
			$asArray = $params->toArray();
		}

		if (empty($asArray))
		{
			// There are no keys; no point in going on.
			return;
		}

		$keys = array_keys($asArray);
		unset($asArray);

		// Loop all page parameter keys
		foreach ($keys as $key)
		{
			// This is the current model state
			$currentState = $model->getState($key);

			// This is the explicitly requested state in the input
			$explicitInput = $model->input->get($key, null, 'raw');

			// If the current state is empty and there's no explicit input we'll use the page parameters instead
			if (!is_null($currentState))
			{
				return;
			}

			if (!is_null($explicitInput))
			{
				return;
			}

			$model->setState($key, $params->get($key));
		}
	}
}
