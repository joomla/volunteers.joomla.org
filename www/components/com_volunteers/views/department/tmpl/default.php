<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>
<div class="row-fluid">
    <div class="filter-bar">
		<?php if ($this->acl->edit): ?>
            <a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=department.edit&id=' . $this->item->id) ?>">
                <span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS_EDIT') ?>
            </a>
		<?php endif; ?>
    </div>
    <div class="page-header">
        <h1>
			<?php echo $this->escape($this->item->title) ?>
        </h1>
    </div>

    <p class="lead"><?php echo strip_tags($this->item->description) ?></p>

    <dl class="dl-horizontal">
		<?php if ($this->item->website): ?>
            <dt><?php echo JText::_('COM_VOLUNTEERS_FIELD_WEBSITE') ?></dt>
            <dd><a href="<?php echo($this->item->website) ?>"><?php echo($this->item->website) ?></a></dd>
		<?php endif; ?>
    </dl>
</div>

<div class="row-fluid">
    <div class="span12">

        <ul id="tab-container" class="nav nav-tabs">
            <li>
                <a href="#members" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_COORDINATORS') ?></a>
            </li>
			<?php if ($this->item->members->honorroll): ?>
                <li>
                    <a href="#honorroll" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_HONORROLL') ?></a>
                </li>
			<?php endif; ?>
            <li>
                <a href="#teams" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_DEPARTMENTTEAMS') ?></a>
            </li>
            <li>
                <a href="#reports" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_REPORTS') ?></a>
            </li>
            <li>
                <a href="#contact" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_CONTACT') ?></a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane" id="members">
				<?php if ($this->acl->edit): ?>
                    <div class="row-fluid">
                        <a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=member.add&department=' . $this->item->id) ?>">
                            <span class="icon-new"></span> <?php echo JText::_('COM_VOLUNTEERS_MEMBER_ADD') ?>
                        </a>
                    </div>
                    <hr>
				<?php endif; ?>
				<?php if ($this->item->members->active): ?>
                    <table class="table table-striped table-hover table-vertical-align">
                        <thead>
                        <th width="30%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_VOLUNTEER') ?></th>
                        <th><?php echo JText::_('COM_VOLUNTEERS_FIELD_POSITION') ?></th>
                        <th width="12%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?></th>
						<?php if ($this->acl->edit): ?>
                            <th width="10%"><?php echo JText::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?></th>
						<?php endif; ?>
                        </thead>
                        <tbody>
						<?php foreach ($this->item->members->active as $volunteer): ?>
                            <tr>
                                <td class="volunteer-image">
                                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer) ?>">
										<?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_name); ?>
										<?php echo $volunteer->volunteer_name; ?>
                                    </a>
                                </td>
                                <td>
									<?php echo $volunteer->position_title; ?>
                                </td>
                                <td>
									<?php echo VolunteersHelper::date($volunteer->date_started, 'M Y'); ?>
                                </td>
								<?php if ($this->acl->edit): ?>
                                    <td>
                                        <a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=member.edit&id=' . $volunteer->id) ?>">
                                            <span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
                                        </a>
                                    </td>
								<?php endif; ?>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
				<?php endif; ?>
            </div>

			<?php if ($this->item->members->honorroll): ?>
                <div class="tab-pane" id="honorroll">
					<?php if ($this->acl->edit): ?>
                        <div class="row-fluid">
                            <a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=member.add&department=' . $this->item->id) ?>">
                                <span class="icon-new"></span> <?php echo JText::_('COM_VOLUNTEERS_MEMBER_ADD') ?>
                            </a>
                        </div>
                        <hr>
					<?php endif; ?>
                    <table class="table table-striped table-hover table-vertical-align">
                        <thead>
                        <th width="30%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_VOLUNTEER') ?></th>
                        <th><?php echo JText::_('COM_VOLUNTEERS_FIELD_POSITION') ?></th>
                        <th width="12%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?></th>
                        <th width="12%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_ENDED') ?></th>
						<?php if ($this->acl->edit): ?>
                            <th width="10%"><?php echo JText::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?></th>
						<?php endif; ?>
                        </thead>
                        <tbody>
						<?php foreach ($this->item->members->honorroll as $volunteer): ?>
                            <tr>
                                <td class="volunteer-image">
                                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer) ?>">
										<?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_name); ?>
										<?php echo $volunteer->volunteer_name; ?>
                                    </a>
                                </td>
                                <td>
									<?php echo $volunteer->position_title; ?>
                                </td>
                                <td>
									<?php echo VolunteersHelper::date($volunteer->date_started, 'M Y'); ?>
                                </td>
                                <td>
									<?php echo VolunteersHelper::date($volunteer->date_ended, 'M Y'); ?>
                                </td>
								<?php if ($this->acl->edit): ?>
                                    <td>
                                        <a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=member.edit&id=' . $volunteer->id) ?>">
                                            <span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
                                        </a>
                                    </td>
								<?php endif; ?>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>

			<?php if ($this->item->teams): ?>
                <div class="tab-pane" id="teams">
					<?php if ($this->acl->create_team): ?>
                        <div class="row-fluid">
                            <a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=team.add&department=' . $this->item->id) ?>">
                                <span class="icon-new"></span> <?php echo JText::_('COM_VOLUNTEERS_TEAM_ADD') ?>
                            </a>
                        </div>
                        <hr>
					<?php endif; ?>
                    <table class="table table-striped table-hover table-vertical-align">
                        <thead>
                        <th><?php echo JText::_('COM_VOLUNTEERS_FIELD_TEAM') ?></th>
                        <th width="20%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_TEAM_LEADER') ?></th>
                        <th width="20%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_TEAM_ASSISTENTLEADER') ?></th>
                        </thead>
                        <tbody>
						<?php foreach ($this->item->teams as $team): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $team->id) ?>">
										<?php echo($team->title) ?>
                                    </a>
                                </td>
                                <td>
									<?php if (!empty($team->leader)) foreach ($team->leader as $volunteer): ?>
                                        <a class="tip hasTooltip" title="<?php echo $volunteer->volunteer_name; ?>" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer) ?>">
											<?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_name); ?>
                                        </a>
									<?php endforeach; ?>
                                </td>
                                <td>
									<?php if (!empty($team->assistantleader)) foreach ($team->assistantleader as $volunteer): ?>
                                        <a class="tip hasTooltip" title="<?php echo $volunteer->volunteer_name; ?>" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer) ?>">
											<?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_name); ?>
                                        </a>
									<?php endforeach; ?>
                                </td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>

            <div class="tab-pane" id="reports">
				<?php if ($this->acl->create_report): ?>
                    <div class="row-fluid">
                        <a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=report.add&department=' . $this->item->id) ?>">
                            <span class="icon-new"></span> <?php echo JText::_('COM_VOLUNTEERS_REPORT_ADD') ?>
                        </a>
                    </div>
                    <hr>
				<?php endif; ?>
				<?php if ($this->item->reports): ?>
					<?php foreach ($this->item->reports as $report): ?>
                        <div class="row-fluid report">
                            <div class="span2 volunteer-image">
                                <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $report->volunteer_id) ?>">
									<?php echo VolunteersHelper::image($report->volunteer_image, 'large', false, $report->volunteer_name); ?>
                                </a>
                            </div>
                            <div class="span10">
								<?php if ($this->acl->edit || ($report->created_by == $this->user->id)): ?>
                                    <a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=report.edit&id=' . $report->id) ?>">
                                        <span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
                                    </a>
								<?php endif; ?>
                                <h2 class="report-title">
                                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id=' . $report->id) ?>">
										<?php echo $report->title; ?>
                                    </a>
                                </h2>
                                <p class="muted">
									<?php echo JText::_('COM_VOLUNTEERS_BY') ?>
                                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $report->volunteer_id) ?>"><?php echo $report->volunteer_name; ?></a>
									<?php echo JText::_('COM_VOLUNTEERS_ON') ?> <?php echo VolunteersHelper::date($report->created, 'Y-m-d H:i'); ?>
									<?php echo JText::_('COM_VOLUNTEERS_IN') ?>
                                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $report->department) ?>"><?php echo $report->department_title; ?></a>
                                </p>
                                <p><?php echo JHtml::_('string.truncate', strip_tags(trim($report->description)), 300); ?></p>
                                <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&id=' . $report->id) ?>" class="btn">
									<?php echo JText::_('COM_VOLUNTEERS_READ_MORE') ?>&nbsp;<?php echo($report->title); ?>
                                </a>
                            </div>
                        </div>
                        <hr>
					<?php endforeach; ?>
					<?php if (count($this->item->reports) == 10): ?>
                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=reports') ?>?filter_category=d.<?php echo $this->item->id; ?>" class="btn">
                            <span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_REPORTS_BROWSE') ?>&nbsp
                        </a>
					<?php endif; ?>
                    <a class="btn btn-warning pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=d.' . $this->item->id . '&format=feed&type=rss') ?>">
                        <span class="icon-feed"></span> <?php echo JText::_('COM_VOLUNTEERS_RSSFEED') ?>
                    </a>
				<?php else: ?>
                    <div class="row-fluid">
                        <p class="alert alert-info">
							<?php echo JText::_('COM_VOLUNTEERS_NOTE_NO_REPORTS') ?>
                        </p>
                    </div>
				<?php endif; ?>
            </div>

            <div class="tab-pane" id="contact">
				<?php if ($this->user->guest) : ?>
                    <p class="alert alert-info">
						<?php echo JText::_('COM_VOLUNTEERS_NOTE_LOGIN_CONTACT_DEPARTMENT') ?>
                    </p>
				<?php else : ?>
                    <form class="form form-horizontal" name="sendmail" action="<?php echo JRoute::_('index.php') ?>" method="post" enctype="multipart/form-data">
                        <div class="control-group">
                            <label class="control-label span2" for="to_name"><?php echo JText::_('COM_VOLUNTEERS_MESSAGE_TO') ?></label>
                            <div class="controls span10">
                                <input type="text" name="to_name" id="to_name" value="<?php echo $this->item->title ?>" class="input-block-level" disabled="disabled"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label span2" for="from_name"><?php echo JText::_('COM_VOLUNTEERS_MESSAGE_FROM') ?></label>
                            <div class="controls span10">
                                <input type="text" name="from_name" id="from_name" value="<?php echo($this->user->name); ?> <<?php echo($this->user->email); ?>>" class="input-block-level" disabled="disabled"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <div class="controls span12">
                                <input type="text" name="subject" id="subject" class="input-block-level" placeholder="<?php echo JText::_('COM_VOLUNTEERS_MESSAGE_SUBJECT') ?>" required/>
                            </div>
                        </div>
                        <div class="control-group">
                            <textarea rows="10" name="message" id="message" class="input-block-level" placeholder="<?php echo JText::sprintf('COM_VOLUNTEERS_MESSAGE_BODY', $this->item->title) ?>" required></textarea>
                        </div>
                        <div class="alert alert-info">
							<?php echo JText::sprintf('COM_VOLUNTEERS_MESSAGE_NOTICE', $this->item->title) ?>
                        </div>
                        <div class="control-group">
                            <input type="submit" value="<?php echo JText::_('COM_VOLUNTEERS_MESSAGE_SUBMIT') ?>" name="submit" id="submitButton" class="btn btn-success pull-right"/>
                        </div>

                        <input type="hidden" name="option" value="com_volunteers"/>
                        <input type="hidden" name="task" value="department.sendmail"/>
						<?php echo JHtml::_('form.token'); ?>
                    </form>
				<?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery('.nav-tabs a:first').tab('show');

    // Javascript to enable link to tab
    var url = document.location.toString();
    if (url.match('#')) {
        jQuery('.nav-tabs a[href="#' + url.split('#')[1] + '"]').tab('show');
    }

    jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = this.href.split('#');
        jQuery('.nav-tabs a').filter('[href="#' + target[1] + '"]').tab('show');
    });

    // Responsive tables
    var headertext = [];
    var headers = document.querySelectorAll("thead");
    var tablebody = document.querySelectorAll("tbody");

    for (var i = 0; i < headers.length; i++) {
        headertext[i] = [];
        for (var j = 0, headrow; headrow = headers[i].rows[0].cells[j]; j++) {
            var current = headrow;
            headertext[i].push(current.textContent);
        }
    }

    for (var h = 0, tbody; tbody = tablebody[h]; h++) {
        for (var i = 0, row; row = tbody.rows[i]; i++) {
            for (var j = 0, col; col = row.cells[j]; j++) {
                col.setAttribute("data-th", headertext[h][j]);
            }
        }
    }
</script>
