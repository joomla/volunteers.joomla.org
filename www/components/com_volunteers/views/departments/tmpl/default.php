<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$active = $this->state->get('filter.active', 1);
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">

	<div class="row-fluid">
		<div class="filter-bar">
			<div class="btn-group pull-right">
				<label class="filter-search-lbl element-invisible" for="filter-search">
					<?php echo JText::_('COM_VOLUNTEERS_SEARCH_DEPARTMENT') . '&#160;'; ?>
				</label>
				<div class="input-append">
					<input type="text" name="filter_search" id="filter-search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" class="inputbox" onchange="document.adminForm.submit();" placeholder="<?php echo JText::_('COM_VOLUNTEERS_SEARCH_DEPARTMENT'); ?>"/>
					<button class="btn btn-primary" type="submit" value="<?php echo JText::_('COM_VOLUNTEERS_SEARCH_DEPARTMENT'); ?>"><span class="icon-search"></span></button>
					<?php if ($this->state->get('filter.search')): ?>
						<button class="btn" type="reset" onclick="jQuery('#filter-search').attr('value', null);document.adminForm.submit();">
							<span class="icon-remove"></span>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="page-header">
			<h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS') ?></h1>
		</div>
	</div>
	<?php if (!empty($this->items)) foreach ($this->items as $i => $item): ?>
		<div class="row-fluid">
			<div class="team well team-<?php echo($item->id); ?>">
				<div class="row-fluid">
					<div class="span8">
						<h2 style="margin-top: 0;">
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $item->id) ?>">
								<?php echo($item->title); ?>
							</a>
						</h2>
						<p><?php echo($item->description); ?></p>
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $item->id) ?>" class="btn">
							<span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_READ_MORE') . ' ' . $item->title; ?>
						</a>
					</div>
					<div class="span4">
						<div class="members">
							<?php $i = 0; ?>
							<?php if (!empty($item->members)) foreach ($item->members as $member): ?>
								<a class="tip hasTooltip" title="<?php echo $member->volunteer_name; ?>" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $member->volunteer) ?>">
									<?php echo VolunteersHelper::image($member->volunteer_image, 'small', false, $member->volunteer_name); ?>
								</a>
								<?php $i++;
								if ($i == 14)
								{
									break;
								}; ?>
							<?php endforeach; ?>
							<?php if (count($item->members) > 14): ?>
								<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $item->id) ?>" class="all-members">
									<span class="all"><?php echo JText::_('COM_VOLUNTEERS_ALL') ?></span><span class="number"><?php echo(' ' . count($item->members)); ?></span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<div class="pagination">
		<p class="counter pull-right">
			<?php echo $this->pagination->getPagesCounter(); ?>
		</p>

		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
</form>