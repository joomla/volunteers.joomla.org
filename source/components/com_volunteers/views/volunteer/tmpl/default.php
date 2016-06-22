<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Get User
$user = JFactory::getUser();
?>
<div class="row-fluid profile">
	<div class="span3 volunteer-image">
		<?php echo VolunteersHelperFormat::image($this->item->image, 'large'); ?>
	</div>
	<div class="span9">
		<?php if((JFactory::getUser()->id == $this->item->user_id) && $this->item->user_id):?>
		<a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&task=edit&id='.$this->item->volunteers_volunteer_id)?>">
			<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_EDIT_PROFILE') ?>
		</a>
		<?php endif;?>
		<h1><?php echo $this->item->firstname?> <?php echo $this->item->lastname?></h1>
		<?php if($this->item->city || $this->item->country):?>
		<p class="muted"><span class="icon-location"></span> <?php echo VolunteersHelperFormat::location($this->item->city, $this->item->country); ?></p>
		<?php endif;?>
		<p class="lead"><?php echo ($this->item->intro)?></p>
	</div>
</div>

<div class="row-fluid">
	<div class="span12">

		<ul class="nav nav-tabs">
			<?php if($this->item->groups):?>
				<li><a href="#groups" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_GROUPS_INVOLVED') ?></a></li>
			<?php endif;?>
			<?php if($this->item->honorroll):?>
				<li><a href="#honorroll" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_HONOR_ROLL') ?></a></li>
			<?php endif;?>
			<?php if($this->item->joomlastory):?>
				<li><a href="#joomlastory" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_JOOMLA_STORY') ?></a></li>
			<?php endif;?>
			<li><a href="#contact" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_CONTACT') ?></a></li>
		</ul>

		<div class="tab-content">
			<?php if($this->item->groups):?>
			<div class="tab-pane fade" id="groups">
				<table class="table table-striped">
					<thead>
						<th><?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME') ?></th>
						<th width="25%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_JOINED') ?></th>
						<?php if((JFactory::getUser()->id == $this->item->user_id) && $this->item->user_id):?>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_EDIT') ?></th>
						<?php endif;?>
					</thead>
					<tbody>
						<?php foreach($this->item->groups as $group): ?>
						<tr>
							<td>
			                  	<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$group->volunteers_group_id)?>">
			                  		<?php echo($group->title)?>
			                  	</a>
							</td>
							<td>
								<?php if($group->role != 1):?>
									<?php echo VolunteersHelperFormat::role($group->role); ?>
								<?php endif;?>
								<?php echo($group->position);?>
							</td>
							<td>
								<?php echo VolunteersHelperFormat::date($group->date_started,'M Y'); ?>
							</td>
							<?php if((JFactory::getUser()->id == $this->item->user_id) && $this->item->user_id):?>
							<td>
								<a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=member&task=edit&id='.$group->volunteers_member_id)?>">
									<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
								</a>
							</td>
							<?php endif;?>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
			</div>
			<?php endif;?>

			<?php if($this->item->honorroll):?>
			<div class="tab-pane fade" id="honorroll">
				<table class="table table-striped">
					<thead>
						<th><?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME') ?></th>
						<th width="25%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_JOINED') ?></th>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_LEFT') ?></th>
					</thead>
					<tbody>
						<?php foreach($this->item->honorroll as $group):?>
						<tr>
							<td>
			                  	<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$group->volunteers_group_id)?>">
			                  		<?php echo($group->title)?>
			                  	</a>
							</td>
							<td>
								<?php if($group->role != 1):?>
									<?php echo VolunteersHelperFormat::role($group->role); ?>
								<?php endif;?>
								<?php echo($group->position);?>
							</td>
							<td>
								<?php echo VolunteersHelperFormat::date($group->date_started,'M Y'); ?>
							</td>
							<td>
								<?php echo VolunteersHelperFormat::date($group->date_ended,'M Y'); ?>
							</td>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
			</div>
			<?php endif;?>

			<?php if($this->item->joomlastory):?>
			<div class="tab-pane fade" id="joomlastory">
				<?php echo (nl2br($this->item->joomlastory))?>
			</div>
			<?php endif;?>

			<div class="tab-pane fade" id="contact">
				<?php if ($user->guest) : ?>
				<p class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_NOTE_LOGIN_CONTACT') ?>
				</p>
				<?php else : ?>
				<dl class="dl-horizontal">
					<?php if($this->item->email):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_EMAIL') ?></dt>
					<dd><a href="mailto:<?php echo ($this->item->email)?>"><?php echo ($this->item->email)?></a></dd>
					<?php endif;?>

					<?php if($this->item->website):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_WEBSITE') ?></dt>
					<dd><a href="http://<?php echo ($this->item->website)?>">http://<?php echo ($this->item->website)?></a></dd>
					<?php endif;?>

					<?php if($this->item->twitter):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_TWITTER') ?></dt>
					<dd><a href="http://twitter.com/<?php echo ($this->item->twitter)?>">http://twitter.com/<?php echo ($this->item->twitter)?></a></dd>
					<?php endif;?>

					<?php if($this->item->facebook):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_FACEBOOK') ?></dt>
					<dd><a href="http://facebook.com/<?php echo ($this->item->facebook)?>">http://facebook.com/<?php echo ($this->item->facebook)?></a></dd>
					<?php endif;?>

					<?php if($this->item->googleplus):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_GOOGLEPLUS') ?></dt>
					<dd><a href="http://plus.google.com/<?php echo ($this->item->googleplus)?>">http://plus.google.com/<?php echo ($this->item->googleplus)?></a></dd>
					<?php endif;?>

					<?php if($this->item->linkedin):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_LINKEDIN') ?></dt>
					<dd><a href="http://www.linkedin.com/in/<?php echo ($this->item->linkedin)?>">http://www.linkedin.com/in/<?php echo ($this->item->linkedin)?></a></dd>
					<?php endif;?>

					<?php if($this->item->github):?>
					<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_GITHUB') ?></dt>
					<dd><a href="http://github.com/<?php echo ($this->item->github)?>">http://github.com/<?php echo ($this->item->github)?></a></dd>
					<?php endif;?>
				</dl>
				<?php endif;?>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	var url = document.location.toString();
	if (url.match('#'))
	{
	    jQuery('.nav-tabs a[href=#'+url.split('#')[1]+']').tab('show') ;
	}
	else
	{
		jQuery('.nav-tabs li:first').addClass('active');
		jQuery('.tab-content .tab-pane:first').addClass('in active');
	}

	jQuery('.nav-tabs a').on('shown', function (e) {
	    if(history.pushState) {
	        history.pushState(null, null, e.target.hash);
	    } else {
	        window.location.hash = e.target.hash;
	    }
	})
</script>