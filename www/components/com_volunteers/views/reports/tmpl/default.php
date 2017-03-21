<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Chosen
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">

	<div class="row-fluid">
		<div class="filter-bar">
			<div class="btn-group pull-right">
				<select name="filter_category" id="filter_category" onchange="document.adminForm.submit();">
					<option value=""><?php echo JText::_('COM_VOLUNTEERS_SELECT_REPORTCATEGORY'); ?></option>
					<?php echo JHtml::_('select.options', JHtmlVolunteers::reportcategories(), 'value', 'text', $this->state->get('filter.category')); ?>
				</select>
			</div>
		</div>
		<div class="page-header">
			<h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_REPORTS') ?><?php if ($this->category): ?>: <?php echo $this->category; ?><?php endif; ?></h1>
		</div>
	</div>

	<?php if (!empty($this->items)): ?>
		<?php foreach ($this->items as $i => $item): ?>
			<div class="row-fluid report">
				<div class="span2">
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->volunteer_id) ?>">
						<?php echo VolunteersHelper::image($item->volunteer_image, 'large', false, $item->volunteer_name); ?>
					</a>
				</div>
				<div class="span10">
					<?php if ($item->acl->edit || ($this->user->id == $item->created_by)): ?>
						<a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=report.edit&id=' . $item->id) ?>">
							<span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
						</a>
					<?php endif; ?>
					<h2 class="report-title">
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id=' . $item->id) ?>">
							<?php echo($item->title); ?>
						</a>
					</h2>
					<p class="muted">
						<?php echo JText::_('COM_VOLUNTEERS_BY') ?>
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->volunteer_id) ?>"><?php echo $item->volunteer_name; ?></a>
						<?php echo JText::_('COM_VOLUNTEERS_ON') ?> <?php echo VolunteersHelper::date($item->created, 'Y-m-d H:i'); ?>
						<?php echo JText::_('COM_VOLUNTEERS_IN') ?>
                        <a href="<?php echo $item->link; ?>"><?php echo $item->name; ?></a>
					</p>
					<p><?php echo JHtml::_('string.truncate', strip_tags(trim($item->description)), 500); ?></p>
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id=' . $item->id) ?>" class="btn">
						<span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_READ_MORE') ?>&nbsp;<?php echo $item->title; ?>
					</a>
				</div>
			</div>
			<hr>
		<?php endforeach; ?>
	<?php else: ?>
		<div class="row-fluid">
			<p class="alert alert-info">
				<?php echo JText::_('COM_VOLUNTEERS_NOTE_NO_REPORTS') ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="row-fluid">
		<a class="btn pull-right btn-warning" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=' . $this->state->get('filter.category') . '&format=feed&type=rss') ?>">
			<span class="icon-feed"></span> <?php echo JText::_('COM_VOLUNTEERS_RSSFEED') ?>
		</a>
	</div>

	<div class="pagination">
		<p class="counter pull-right">
			<?php echo $this->pagination->getPagesCounter(); ?>
		</p>

		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
</form>