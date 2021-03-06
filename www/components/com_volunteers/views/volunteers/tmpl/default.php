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

	<div class="volunteers">
		<div class="row-fluid">
			<div class="filter-bar">
				<div class="btn-group pull-right">
					<label class="filter-search-lbl element-invisible" for="filter-search">
						<?php echo JText::_('COM_VOLUNTEERS_SEARCH_VOLUNTEER') . '&#160;'; ?>
					</label>
					<div class="input-append">
						<input type="text" name="filter_search" id="filter-search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" class="inputbox" onchange="document.adminForm.submit();" placeholder="<?php echo JText::_('COM_VOLUNTEERS_SEARCH_VOLUNTEER'); ?>"/>
						<button class="btn btn-primary" type="submit" value="<?php echo JText::_('COM_VOLUNTEERS_SEARCH_VOLUNTEER'); ?>">
							<span class="icon-search"></span></button>
						<?php if ($this->state->get('filter.search')): ?>
							<button class="btn" type="reset" onclick="jQuery('#filter-search').attr('value', null);document.adminForm.submit();">
								<span class="icon-remove"></span>
							</button>
						<?php endif; ?>
					</div>
				</div>

                <fieldset id="filter_active" class="btn-group radio pull-right" onchange="document.adminForm.submit();">
                    <input type="radio" id="filter_active1" name="filter_active" value="1" <?php if ($active == 1): ?>selected="selected"<?php endif; ?>>
                    <label for="filter_active1" class="btn<?php if ($active == 1): ?> btn-success<?php endif; ?>"><?php echo JText::_('COM_VOLUNTEERS_ACTIVE') ?></label>

                    <input type="radio" id="filter_active2" name="filter_active" value="2" <?php if ($active == 2): ?>selected="selected"<?php endif; ?>>
                    <label for="filter_active2" class="btn<?php if ($active == 2): ?> btn-inverse<?php endif; ?>"><?php echo JText::_('COM_VOLUNTEERS_ALL') ?></label>
                </fieldset>
			</div>
			<div class="page-header">
				<h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS') ?></h1>
			</div>
		</div>

		<div class="row-fluid">
			<?php $i = 0; ?>
			<?php if (!empty($this->items)) foreach ($this->items as $item): ?>
			<div class="span2">
				<div class="well well-small">
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id) ?>">
						<?php echo VolunteersHelper::image($item->image, 'large', false, $item->name); ?>
					</a>
					<h4 class="text-center">
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id) ?>">
							<?php echo $item->name; ?>
						</a>
					</h4>
				</div>
			</div>
			<?php $i++; ?>
			<?php if ($i == 6): ?>
		</div>
		<div class="row-fluid">
			<?php $i = 0; ?>
			<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<div class="pagination">
			<p class="counter pull-right">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>

			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	</div>
</form>