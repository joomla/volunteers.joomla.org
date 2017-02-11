<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>
<?php if ($this->item->new): ?>
    <div class="alert alert-success">
        <h1>
			<?php echo JText::_('COM_VOLUNTEERS_PROFILE_NEW_COMPLETED') ?>
        </h1>

        <p class="lead">
			<?php echo JText::_('COM_VOLUNTEERS_WELCOME') ?>
        </p>
    </div>
<?php endif; ?>

<div class="row-fluid profile">
    <div class="span3 volunteer-image">
		<?php echo VolunteersHelper::image($this->item->image, 'large', false, $this->item->name); ?>
    </div>
    <div class="span9">
        <div class="filter-bar">
			<?php if (($this->user->id == $this->item->user_id) && $this->item->user_id): ?>
                <a class="btn pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . $this->item->id) ?>">
                    <span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS_EDIT_MY') ?>
                </a>
			<?php endif; ?>
        </div>
        <div class="page-header">
            <h1><?php echo $this->item->name; ?></h1>
        </div>

		<?php if ($this->item->city || $this->item->country): ?>
            <p class="muted">
                <span class="icon-location"></span> <?php echo VolunteersHelper::location($this->item->city, $this->item->country); ?>
            </p>
		<?php endif; ?>

        <p class="lead"><?php echo($this->item->intro) ?></p>

        <div class="btn-group">
			<?php if ($this->item->website && ($this->item->website != 'http://')): ?>
                <a class="btn" target="_blank" href="<?php echo($this->item->website) ?>">
                    <span class="icon-link"></span> <span class="hidden-phone"><?php echo JText::_('COM_VOLUNTEERS_CONNECT_WEBSITE') ?></span>
                </a>
			<?php endif; ?>
			<?php if ($this->item->twitter): ?>
                <a class="btn btn-twitter" target="_blank" href="https://twitter.com/<?php echo($this->item->twitter) ?>">
                    <span class="icon-twitter"></span> <span class="hidden-phone"><?php echo JText::_('COM_VOLUNTEERS_CONNECT_TWITTER') ?></span>
                </a>
			<?php endif; ?>
			<?php if ($this->item->facebook): ?>
                <a class="btn btn-facebook" target="_blank" href="https://www.facebook.com/<?php echo($this->item->facebook) ?>">
                    <span class="icon-facebook"></span> <span class="hidden-phone"><?php echo JText::_('COM_VOLUNTEERS_CONNECT_FACEBOOK') ?></span>
                </a>
			<?php endif; ?>
			<?php if ($this->item->googleplus): ?>
                <a class="btn btn-google-plus" target="_blank" href="https://plus.google.com/<?php echo($this->item->googleplus) ?>">
                    <span class="icon-google-plus"></span> <span class="hidden-phone"><?php echo JText::_('COM_VOLUNTEERS_CONNECT_GOOGLEPLUS') ?></span>
                </a>
			<?php endif; ?>
			<?php if ($this->item->linkedin): ?>
                <a class="btn btn-linkedin" target="_blank" href="https://www.linkedin.com/in/<?php echo($this->item->linkedin) ?>">
                    <span class="icon-linkedin"></span> <span class="hidden-phone"><?php echo JText::_('COM_VOLUNTEERS_CONNECT_LINKEDIN') ?></span>
                </a>
			<?php endif; ?>
			<?php if ($this->item->github): ?>
                <a class="btn btn-gtihub" target="_blank" href="https://github.com/<?php echo($this->item->github) ?>">
                    <span class="icon-github"></span> <span class="hidden-phone"><?php echo JText::_('COM_VOLUNTEERS_CONNECT_GITHUB') ?></span>
                </a>
			<?php endif; ?>
        </div>
    </div>
</div>

<br>

<div class="row-fluid">
    <div class="span12">

        <ul id="tab-container" class="nav nav-tabs">
			<?php if ($this->item->teams->active): ?>
                <li>
                    <a href="#teams" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_TEAMSINVOLVED') ?></a>
                </li>
			<?php endif; ?>
			<?php if ($this->item->teams->honorroll): ?>
                <li>
                    <a href="#honorroll" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_HONORROLL') ?></a>
                </li>
			<?php endif; ?>
			<?php if ($this->item->joomlastory): ?>
                <li>
                    <a href="#joomlastory" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_JOOMLASTORY') ?></a>
                </li>
			<?php endif; ?>
            <li>
                <a href="#contact" data-toggle="tab"><?php echo JText::_('COM_VOLUNTEERS_TAB_CONTACT') ?></a>
            </li>
        </ul>

        <div class="tab-content">
			<?php if ($this->item->teams->active): ?>
                <div class="tab-pane" id="teams">
                    <table class="table table-striped table-hover table-vertical-align">
                        <thead>
                        <th width="30%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_TEAM') ?></th>
                        <th width="20%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_POSITION') ?></th>
                        <th><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
                        <th width="12%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?></th>
                        </thead>
                        <tbody>
						<?php foreach ($this->item->teams->active as $team): ?>
                            <tr>
                                <td>
									<?php if ($team->team): ?>
                                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $team->team) ?>">
											<?php echo($team->team_title) ?>
                                        </a>
									<?php else: ?>
                                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $team->department) ?>">
											<?php echo($team->department_title) ?>
                                        </a>
									<?php endif; ?>
                                </td>
                                <td>
									<?php echo($team->position_title) ?>
                                </td>
                                <td>
									<?php echo($team->role_title) ?>
                                </td>
                                <td>
									<?php echo VolunteersHelper::date($team->date_started, 'M Y'); ?>
                                </td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>

			<?php if ($this->item->teams->honorroll): ?>
                <div class="tab-pane" id="honorroll">
                    <table class="table table-striped table-hover table-vertical-align">
                        <thead>
                        <th width="30%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_TEAM') ?></th>
                        <th width="20%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_POSITION') ?></th>
                        <th><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
                        <th width="12%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?></th>
                        <th width="12%"><?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_ENDED') ?></th>
                        </thead>
                        <tbody>
						<?php foreach ($this->item->teams->honorroll as $team): ?>
                            <tr>
                                <td>
									<?php if ($team->team): ?>
                                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $team->team) ?>">
											<?php echo($team->team_title) ?>
                                        </a>
									<?php else: ?>
                                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id=' . $team->department) ?>">
											<?php echo($team->department_title) ?>
                                        </a>
									<?php endif; ?>
                                </td>
                                <td>
									<?php echo($team->position_title) ?>
                                </td>
                                <td>
									<?php echo($team->role_title) ?>
                                </td>
                                <td>
									<?php echo VolunteersHelper::date($team->date_started, 'M Y'); ?>
                                </td>
                                <td>
									<?php echo VolunteersHelper::date($team->date_ended, 'M Y'); ?>
                                </td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>

			<?php if ($this->item->joomlastory): ?>
                <div class="tab-pane" id="joomlastory">
					<?php echo(nl2br($this->item->joomlastory)) ?>
                </div>
			<?php endif; ?>

            <div class="tab-pane" id="contact">
				<?php if ($this->user->guest) : ?>
                    <p class="alert alert-info">
						<?php echo JText::_('COM_VOLUNTEERS_NOTE_LOGIN_CONTACT_VOLUNTEER') ?>
                    </p>
				<?php else : ?>
                    <form class="form form-horizontal" name="sendmail" action="<?php echo JRoute::_('index.php') ?>" method="post" enctype="multipart/form-data">
                        <div class="control-group">
                            <label class="control-label span2" for="to_name"><?php echo JText::_('COM_VOLUNTEERS_MESSAGE_TO') ?></label>
                            <div class="controls span10">
                                <input type="text" name="to_name" id="to_name" value="<?php echo $this->item->name; ?>" class="input-block-level" disabled="disabled"/>
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
                            <textarea rows="10" name="message" id="message" class="input-block-level" placeholder="<?php echo JText::sprintf('COM_VOLUNTEERS_MESSAGE_BODY', $this->item->name) ?>" required></textarea>
                        </div>
                        <div class="alert alert-info">
							<?php echo JText::sprintf('COM_VOLUNTEERS_MESSAGE_NOTICE', $this->item->name) ?>
                        </div>
                        <div class="control-group">
                            <input type="submit" value="<?php echo JText::_('COM_VOLUNTEERS_MESSAGE_SUBMIT') ?>" name="submit" id="submitButton" class="btn btn-success pull-right"/>
                        </div>

                        <input type="hidden" name="option" value="com_volunteers"/>
                        <input type="hidden" name="task" value="volunteer.sendmail"/>
						<?php echo JHtml::_('form.token'); ?>
                    </form>
				<?php endif; ?>
            </div>
        </div>

		<?php if ($this->user->id && ($this->user->id != $this->item->user_id)): ?>
            <a class="btn btn-danger js-reportspam" data-volunteer="<?php echo $this->item->id; ?>" data-success="<?php echo JText::_('COM_VOLUNTEERS_SPAM_REPORT_SUCCESS') ?>">
                <span class="icon-warning"></span> <?php echo JText::_('COM_VOLUNTEERS_SPAM_REPORT') ?>
            </a>
		<?php endif; ?>
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

    // Report Spam Button
    var reportspambutton = jQuery('.js-reportspam');
    if (reportspambutton) {
        reportspambutton.click(function (e) {
            e.preventDefault();
            var item = jQuery(this),
                request = {
                    'option': 'com_ajax',
                    'plugin': 'reportspam',
                    'format': 'json',
                    'volunteer': item.attr('data-volunteer')
                };

            jQuery.ajax({
                type: 'POST',
                data: request,
                success: function (response) {
                    item.removeClass('btn-danger').addClass('btn-success').html('<span class="icon-thumbs-up"></span> ' + item.attr('data-success'));
                }
            });
        });
    }

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
