<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Controller;

// Protect from unauthorized access
use FOF40\Container\Container;

defined('_JEXEC') || die();

/**
 * Include off-site directories controller
 */
class IncludeFolders extends DatabaseFilters
{
	/**
	 * Overridden constructor. Sets the correct model and view names.
	 *
	 * @param   Container  $container  The component's container
	 * @param   array      $config     Optional configuration overrides
	 */
	public function __construct(Container $container, array $config)
	{
		if (!is_array($config))
		{
			$config = [];
		}

		$config = array_merge([
			'modelName'	=> 'IncludeFolders',
			'viewName'	=> 'IncludeFolders'
		], $config);

		parent::__construct($container, $config);

		$this->decodeJsonAsArray = true;
	}

}
