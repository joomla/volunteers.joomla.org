<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Model\DatabasefiltersModel;
use Akeeba\Engine\Factory;

trait FilterRoots
{
	/**
	 * @param   string  $target
	 *
	 * @return  array
	 *
	 * @since   7.5.0
	 */
	private function getRoots(string $target): array
	{
		$filters   = Factory::getFilters();
		$output    = [];

		switch ($target)
		{
			case 'fs':
				$rootInfo = $filters->getInclusions('dir');

				foreach ($rootInfo as $item)
				{
					$output[] = $item[0];
				}

				break;

			case 'db':
				/** @var DatabasefiltersModel $model */
				$model = $this->getMVCFactory()->createModel('Databasefilters', 'Administrator');
				$rootInfo = $model->getRoots();

				foreach ($rootInfo as $item)
				{
					$output[] = $item->value;
				}

				break;
		}

		return $output;
	}

}
