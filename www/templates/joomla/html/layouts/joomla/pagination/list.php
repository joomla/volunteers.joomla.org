<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/** @var array $list */
$list = $displayData['list'];

// Calculate to display range of pages
$currentPage = 1;
$range       = 1;
$step        = 5;

foreach ($list['pages'] as $k => $page)
{
	if (!$page['active'])
	{
		$currentPage = $k;
	}
}

if ($currentPage >= $step)
{
	if ($currentPage % $step == 0)
	{
		$range = ceil($currentPage / $step) + 1;
	}
	else
	{
		$range = ceil($currentPage / $step);
	}
}
?>

<ul class="pagination-list">
	<?php echo $list['start']['data']; ?>
	<?php echo $list['previous']['data']; ?>
	<?php foreach ($list['pages'] as $k => $page) : ?>
		<?php if (in_array($k, range($range * $step - ($step + 1), $range * $step))) : ?>
			<?php if (($k % $step == 0 || $k == $range * $step - ($step + 1)) && $k != $currentPage && $k != $range * $step - $step) : ?>
				<?php $page['data'] = preg_replace('#(<a.*?>).*?(</a>)#', '$1...$2', $page['data']); ?>
			<?php endif; ?>
		<?php endif; ?>
		<?php echo $page['data']; ?>
	<?php endforeach; ?>
	<?php echo $list['next']['data']; ?>
	<?php echo $list['end']['data']; ?>
</ul>
