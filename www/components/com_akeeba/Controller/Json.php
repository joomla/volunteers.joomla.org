<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Controller;

// Protect from unauthorized access
defined('_JEXEC') || die();

use FOF40\Container\Container;
use FOF40\Controller\Controller;
use FOF40\Controller\Mixin\PredefinedTaskList;

/**
 * Controller for the JSON API
 */
class Json extends Controller
{
	use PredefinedTaskList;

	/**
	 * Overridden constructor
	 *
	 * @param   Container  $container  The application container
	 * @param   array      $config     The configuration array
	 */
	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['json']);
	}

	/**
	 * Handles API calls
	 */
	public function json()
	{
		// Use the model to parse the JSON message
		if (function_exists('ob_start'))
		{
			@ob_start();
		}

		$sourceJSON = $this->input->get('json', null, 'raw', 2);

		/** @var \Akeeba\Backup\Site\Model\Json $model */
		$model = $this->getModel();
		$json  = $model->execute($sourceJSON);

		if (function_exists('ob_end_clean'))
		{
			@ob_end_clean();
		}

		// Just dump the JSON and tear down the application, without plugins executing
		header('Content-type: text/plain');
		header('Connection: close');
		echo $json;

		$this->container->platform->closeApplication();
	}

}
