<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die;

use Joomla\CMS\MVC\Model\ListModel;

trait ViewListLimitFixTrait
{
	public function fixListLimitPastTotal(ListModel $model, ?callable $getTotal = null): void
	{
		$start = $model->getState('list.start');
		$limit = $model->getState('list.limit', 10);
		$total = call_user_func($getTotal ?? fn() => $model->getTotal());

		if ($start >= $total)
		{
			$pages = $limit > 0 ? ceil($total / $limit) : 1;
			$start = max(0, $limit * ($pages - 1));

			$model->setState('list.start', $start);
		}

	}
}