<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>

<div class="row-fluid report">
	<div class="span2 volunteer-image">
		<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->item->volunteer_id) ?>">
			<?php echo VolunteersHelper::image($this->item->volunteer_image, 'large', false, $this->item->volunteer_name); ?>
		</a>
	</div>
	<div class="span10">
		<div class="filter-bar">
			<?php if ($this->acl->edit || ($this->item->created_by == $this->user->id)): ?>
				<a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=report.edit&id=' . $this->item->id) ?>">
					<span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="page-header">
			<h1><?php echo $this->item->title ?></h1>
		</div>

		<p class="muted">
			<?php echo JText::_('COM_VOLUNTEERS_BY') ?>
			<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->item->volunteer_id) ?>"><?php echo $this->item->volunteer_name; ?></a>
			<?php echo JText::_('COM_VOLUNTEERS_ON') ?>
			<?php echo VolunteersHelper::date($this->item->created, 'Y-m-d H:i'); ?>
			<?php echo JText::_('COM_VOLUNTEERS_IN') ?>
            <a href="<?php echo $this->item->link; ?>"><?php echo $this->item->name; ?></a>
		</p>

		<?php echo($this->item->description) ?>

	</div>
</div>

<div class="share">
	<?php echo $this->share; ?>
</div>
