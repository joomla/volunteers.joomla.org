<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$this->loadHelper('params');
$this->loadHelper('modules');
$this->loadHelper('format');
?>
<div class="row-fluid">
	<?php if($this->roles->lead):?>
		<a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&task=edit&id='.$this->item->volunteers_group_id)?>">
			<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_EDIT_GROUP') ?>
		</a>
	<?php endif;?>
	<h1>
		<?php echo $this->escape($this->item->title)?>
		<?php if($this->item->acronym):?> (<?php echo ($this->item->acronym)?>)<?php endif;?>
	</h1>

	<p class="lead"><?php echo ($this->item->description)?></p>

	<dl class="dl-horizontal">
		<?php if($this->item->website):?>
		<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_WEBSITE') ?></dt>
		<dd><a href="<?php echo ($this->item->website)?>"><?php echo ($this->item->website)?></a></dd>
		<?php endif;?>

		<?php if(($this->item->ownership) && ($this->item->ownership != 1)):?>
		<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_OWNERSHIP') ?></dt>
		<dd><?php echo VolunteersHelperFormat::ownership($this->item->ownership); ?></dd>
		<?php endif;?>

		<?php if($this->item->date_started != '0000-00-00'):?>
		<dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?></dt>
		<dd><?php echo VolunteersHelperFormat::date($this->item->date_started,'F Y'); ?></dd>
		<?php endif;?>
	</dl>
</div>

<div class="row-fluid">
	<div class="span12">

		<ul class="nav nav-tabs">
			<li class="active"><a href="#members" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_GROUP_MEMBERS') ?></a></li>
			<?php if($this->honorroll):?>
			<li><a href="#honorroll" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_HONOR_ROLL') ?></a></li>
			<?php endif;?>
			<li><a href="#reports" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_REPORTS') ?></a></li>
			<li><a href="#getinvolved" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_GET_INVOLVED') ?></a></li>
		</ul>

		<div class="tab-content">
			<div class="tab-pane fade in active" id="members">
				<?php if($this->roles->lead || $this->roles->liaison):?>
				<div class="row-fluid">
					<a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=groupmember&task=add&group='.$this->item->volunteers_group_id)?>">
						<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_ADD_GROUPMEMBER') ?>
					</a>
				</div>
				<hr>
				<?php endif;?>
				<?php if($this->groupmembers):?>
				<table class="table table-striped">
					<thead>
						<th width="50px"></th>
						<th><?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME') ?></th>
						<th width="25%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_JOINED') ?></th>
						<?php if($this->roles->member):?>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_EDIT') ?></th>
						<?php endif;?>
					</thead>
					<tbody>
						<?php foreach($this->groupmembers as $volunteer):?>
						<tr>
							<td class="volunteer-image">
								<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$volunteer->volunteers_volunteer_id)?>">
									<?php echo VolunteersHelperFormat::image($volunteer->volunteer_image, 'small'); ?>
								</a>
							</td>
							<td>
			                  	<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$volunteer->volunteers_volunteer_id)?>">
			                  		<?php echo($volunteer->volunteer_firstname)?> <?php echo($volunteer->volunteer_lastname)?>
			                  	</a>
							</td>
							<td>
								<?php if($volunteer->position):?>
									<?php echo($volunteer->position);?>
								<?php elseif($volunteer->role != 1):?>
									<?php echo VolunteersHelperFormat::role($volunteer->role); ?>
								<?php endif;?>
							</td>
							<td>
								<?php echo VolunteersHelperFormat::date($volunteer->date_started,'M Y'); ?>
							</td>
							<?php if($this->roles->member):?>
							<td>
								<?php if(($this->roles->lead) || ($volunteer->volunteer_user_id == JFactory::getUser()->id)):?>
								<a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=groupmember&task=edit&id='.$volunteer->volunteers_groupmember_id)?>">
									<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
								</a>
								<?php endif;?>
							</td>
							<?php endif;?>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
				<?php endif;?>
			</div>

			<?php if($this->honorroll):?>
			<div class="tab-pane fade" id="honorroll">
				<table class="table table-striped">
					<thead>
						<th width="50px"></th>
						<th><?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME') ?></th>
						<th width="25%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_JOINED') ?></th>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_LEFT') ?></th>
						<?php if($this->roles->lead || $this->roles->liaison):?>
						<th width="15%"><?php echo JText::_('COM_VOLUNTEERS_EDIT') ?></th>
						<?php endif;?>
					</thead>
					<tbody>
						<?php foreach($this->honorroll as $volunteer):?>
						<tr>
							<td class="volunteer-image">
								<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$volunteer->volunteers_volunteer_id)?>">
									<?php echo VolunteersHelperFormat::image($volunteer->volunteer_image, 'small'); ?>
								</a>
							</td>
							<td>
			                  	<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$volunteer->volunteers_volunteer_id)?>">
			                  		<?php echo($volunteer->volunteer_firstname)?> <?php echo($volunteer->volunteer_lastname)?>
			                  	</a>
							</td>
							<td>
								<?php if($volunteer->role != 1):?>
									<?php echo VolunteersHelperFormat::role($volunteer->role); ?>
								<?php endif;?>
								<?php echo($volunteer->position);?>
							</td>
							<td>
								<?php echo VolunteersHelperFormat::date($volunteer->date_started,'M Y'); ?>
							</td>
							<td>
								<?php echo VolunteersHelperFormat::date($volunteer->date_ended,'M Y'); ?>
							</td>
							<?php if($this->roles->lead):?>
							<td>
								<a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=groupmember&task=edit&id='.$volunteer->volunteers_groupmember_id)?>">
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

			<div class="tab-pane fade" id="reports">
				<?php if($this->roles->member):?>
				<div class="row-fluid">
					<a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&task=add&group='.$this->item->volunteers_group_id)?>">
						<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_ADD_REPORTS') ?>
					</a>
				</div>
				<hr>
				<?php endif;?>
				<?php if($this->reports):?>
					<?php foreach($this->reports as $report):?>
					<div class="row-fluid report">
						<div class="span2 volunteer-image">
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$report->volunteer_id)?>">
								<?php echo VolunteersHelperFormat::image($report->volunteer_image, 'small'); ?>
							</a>
						</div>
						<div class="span10">
							<?php if($report->created_by == JFactory::getUser()->id):?>
							<a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&task=edit&id='.$report->volunteers_report_id.'&group='.$report->group_id)?>">
								<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
							</a>
							<?php endif;?>
							<h2 class="report-title">
								<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id='.$report->volunteers_report_id)?>">
									<?php echo($report->title);?>
								</a>
							</h2>
							<p class="muted">
								<?php echo JText::_('COM_VOLUNTEERS_BY') ?> <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$report->volunteer_id)?>"><?php echo($report->volunteer_firstname)?> <?php echo($report->volunteer_lastname)?></a>
								<?php echo JText::_('COM_VOLUNTEERS_ON') ?> <?php echo VolunteersHelperFormat::date($report->created_on,'Y-m-d H:i'); ?>
								<?php echo JText::_('COM_VOLUNTEERS_IN') ?> <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$report->group_id)?>"><?php echo($report->group_title)?></a>
							</p>
							<p><?php echo JHtml::_('string.truncate', strip_tags(trim($report->description)), 300); ?></p>
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id='.$report->volunteers_report_id)?>" class="btn">
								<?php echo JText::_('COM_VOLUNTEERS_READ_MORE') ?> <?php echo($report->title);?>
							</a>
						</div>
					</div>
					<hr>
					<?php endforeach;?>
				<?php else:?>
				<div class="row-fluid">
					<p class="alert alert-info">
						<?php echo JText::_('COM_VOLUNTEERS_NOTE_NO_REPORTS') ?>
					</p>
				</div>
				<?php endif;?>
			</div>

			<div class="tab-pane fade" id="getinvolved">
				<?php if($this->item->getinvolved):?>
					<?php echo ($this->item->getinvolved);?>
				<?php else:?>
					<p class="alert alert-info">
						<?php echo JText::_('COM_VOLUNTEERS_NOTE_NO_GETINVOLVED') ?>
					</p>
				<?php endif;?>
			</div>

		</div>
	</div>
</div>
<script type="text/javascript">
	var url = document.location.toString();
	if (url.match('#')) {
	    jQuery('.nav-tabs a[href=#'+url.split('#')[1]+']').tab('show') ;
	}

	jQuery('.nav-tabs a').on('shown', function (e) {
	    if(history.pushState) {
	        history.pushState(null, null, e.target.hash);
	    } else {
	        window.location.hash = e.target.hash; //Polyfill for old browsers
	    }
	})
</script>