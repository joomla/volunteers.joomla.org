<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Board\HtmlView $this */

// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
?>
<div class="row">
    <div class="filter-bar">
        <?php if ($this->acl->edit) : ?>
            <a  href="<?php echo Route::_('index.php?option=com_volunteers&task=department.edit&id=' . $this->item->id) ?>">
                <button class="vol-button-admin" role="button">
                <span class="icon-edit" aria-hidden="true"></span>
                    <?php echo Text::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS_EDIT') ?></button>
            </a>
        <?php endif; ?>
    </div>
    <div class="border-bottom pb-2 mb-3">
        <h1 class="vol_h1">
            <?php echo $this->escape($this->item->title) ?>
        </h1>
    </div>

    <p class="lead">
        <?php echo strip_tags((string) $this->item->description) ?>
    </p>

    <dl class="row">
        <?php if ($this->item->website) : ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_WEBSITE') ?>
            </dt>
            <dd class="col-sm"><a href="<?php echo ($this->item->website) ?>"><?php echo ($this->item->website) ?></a></dd>
        <?php endif; ?>
    </dl>
</div>

<div class="row">
    <div class="col-12">
        <?php
        echo HTMLHelper::_('uitab.startTabSet', 'departmentTab', ['active' => 'viewmembers', 'recall' => true, 'breakpoint' => 768]);
        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewmembers', Text::_('COM_VOLUNTEERS_TAB_MEMBERS'));
        ?>
        <?php if ($this->acl->edit) : ?>
            <div class="row">
                <a href="<?php echo Route::_('index.php?option=com_volunteers&task=member.add&department=' . $this->item->id) ?>">
                    <button class="vol-button-admin" role="button">
                    <span class="icon-new" aria-hidden="true"></span>
                        <?php echo Text::_('COM_VOLUNTEERS_MEMBER_ADD') ?></button>
                </a>
            </div>
            <hr>
        <?php endif; ?>
        <?php if ($this->item->members->active) : ?>
            <table class="table table-striped table-hover table-vertical-align">
                <thead>
                    <th width="30%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_VOLUNTEER') ?>
                    </th>
                    <th>
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION') ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?>
                    </th>
                    <?php if ($this->acl->edit) : ?>
                        <th width="10%">
                            <?php echo Text::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?>
                        </th>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php foreach ($this->item->members->active as $volunteer) : ?>
                        <tr>
                            <td class="volunteer-image">
                                <?php VolunteersHelper::OutputVolunteer($volunteer); ?>
                            </td>
                            <td>
                                <?php if ($volunteer->position == 11) : ?>
                                    <?php echo $volunteer->position_title; ?>:
                                    <a
                                        href="<?php echo Route::_('index.php?option=com_volunteers&view=department&id=' . $volunteer->department) ?>">
                                        <?php echo $volunteer->department_title; ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo $volunteer->role_title; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo VolunteersHelper::date($volunteer->date_started, 'M Y'); ?>
                            </td>
                            <?php if ($this->acl->edit) : ?>
                                <td>
                                    <a 
                                        href="<?php echo Route::_('index.php?option=com_volunteers&task=member.edit&id=' . $volunteer->id) ?>">
                                        <button class="vol-button-admin" role="button">
                                        <span class="icon-edit" aria-hidden="true"></span>
                                            <?php echo Text::_('COM_VOLUNTEERS_EDIT') ?></button>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;
        echo HTMLHelper::_('uitab.endTab');

        if ($this->item->members->honorroll) {
            echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewhonourroll', Text::_('COM_VOLUNTEERS_TAB_HONORROLL'));
            ?>
            <?php if ($this->acl->edit) : ?>
                <div class="row">
                    <a href="<?php echo Route::_('index.php?option=com_volunteers&task=member.add&department=' . $this->item->id) ?>">
                        <button class="vol-button-admin" role="button">
                            <span class="icon-new" aria-hidden="true"></span>
                            <?php echo Text::_('COM_VOLUNTEERS_MEMBER_ADD') ?></button>
                    </a>
                </div>
                <hr>
            <?php endif; ?>
            <table class="table table-striped table-hover table-vertical-align">
                <thead>
                    <th width="30%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_VOLUNTEER') ?>
                    </th>
                    <th>
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION') ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_ENDED') ?>
                    </th>
                    <?php if ($this->acl->edit) : ?>
                        <th width="10%">
                            <?php echo Text::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?>
                        </th>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php foreach ($this->item->members->honorroll as $volunteer) : ?>
                        <tr>
                            <td class="volunteer-image">
                                <?php VolunteersHelper::OutputVolunteer($volunteer); ?>
                            </td>
                            <td>
                                <?php if ($volunteer->position == 11) : ?>
                                    <?php echo $volunteer->position_title; ?>:
                                    <a
                                        href="<?php echo Route::_('index.php?option=com_volunteers&view=department&id=' . $volunteer->department) ?>">
                                        <?php echo $volunteer->department_title; ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo $volunteer->role_title; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo VolunteersHelper::date($volunteer->date_started, 'M Y'); ?>
                            </td>
                            <td>
                                <?php echo VolunteersHelper::date($volunteer->date_ended, 'M Y'); ?>
                            </td>
                            <?php if ($this->acl->edit) : ?>
                                <td>
                                    <a 
                                        href="<?php echo Route::_('index.php?option=com_volunteers&task=member.edit&id=' . $volunteer->id) ?>">
                                        <button class="vol-button-admin" role="button">
                                        <span class="icon-edit" aria-hidden="true"></span>
                                            <?php echo Text::_('COM_VOLUNTEERS_EDIT') ?></button>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>


            <?php echo HTMLHelper::_('uitab.endTab');
        }

        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewreports', Text::_('COM_VOLUNTEERS_TAB_REPORTS'));
        ?>
        <?php if ($this->acl->create_report) : ?>
            <div class="row">
                <a href="<?php echo Route::_('index.php?option=com_volunteers&task=report.add&department=' . $this->item->id) ?>">
                    <button class="vol-button-admin" role="button">
                    <span class="icon-new" aria-hidden="true"></span>
                        <?php echo Text::_('COM_VOLUNTEERS_REPORT_ADD') ?></button>
                </a>
            </div>
            <hr>
        <?php endif; ?>
        <?php if ($this->item->reports) : ?>
            <?php foreach ($this->item->reports as $report) : ?>
                <div class="row report">
                    <div class="col-2 volunteer-image">
                        <a
                            href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $report->volunteer_id) ?>">
                            <?php echo VolunteersHelper::image($report->volunteer_image, 'large', false, $report->volunteer_name); ?>
                        </a>
                    </div>
                    <div class="col-8">
                        <?php if ($this->acl->edit || ($report->created_by == $this->user->id)) : ?>
                            <a 
                                href="<?php echo Route::_('index.php?option=com_volunteers&task=report.edit&id=' . $report->id) ?>">
                                <button class="vol-button-admin" role="button">
                                <span class="icon-edit" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_VOLUNTEERS_EDIT') ?></button>
                            </a>
                        <?php endif; ?>
                        <h2 class="vol_h2">
                            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=report&id=' . $report->id) ?>">
                                <?php echo $report->title; ?>
                            </a>
                        </h2>
                        <p class="text-muted">
                            <?php echo Text::_('COM_VOLUNTEERS_BY') ?>
                            <a
                                href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $report->volunteer_id) ?>"><?php echo $report->volunteer_name; ?></a>
                            <?php echo Text::_('COM_VOLUNTEERS_ON') ?>
                            <?php echo VolunteersHelper::date($report->created, 'Y-m-d H:i'); ?>
                            <?php echo Text::_('COM_VOLUNTEERS_IN') ?>
                            <a
                                href="<?php echo Route::_('index.php?option=com_volunteers&view=board&id=' . $report->department) ?>"><?php echo $report->department_title; ?></a>
                        </p>
                        <p>
                            <?php echo HtmlHelper::_('string.truncate', strip_tags(trim((string) $report->description)), 300); ?>
                        </p>
                        <a href="<?php echo Route::_('index.php?option=com_volunteers&view=report&id=' . $report->id) ?>"
                            class="btn btn-light">
                            <?php echo Text::_('COM_VOLUNTEERS_READ_MORE') ?>&nbsp;<?php echo ($report->title); ?>
                        </a>
                    </div>
                </div>
                <hr style="margin: 22px 0;  border: 0;  border-top: 1px solid #eee; border-bottom: 1px solid #fff">
            <?php endforeach; ?>
            <?php if (count($this->item->reports) == 10) : ?>
                <a href="<?php echo Route::_('index.php?option=com_volunteers&view=reports') ?>?filter_category=d.<?php echo $this->item->id; ?>"
                    class="btn btn-light">
                    <span class="icon-chevron-right" aria-hidden="true"></span>
                    <?php echo Text::_('COM_VOLUNTEERS_REPORTS_BROWSE') ?>&nbsp
                </a>
            <?php endif; ?>
            <a class="btn btn-warning float-end"
                href="<?php echo Route::_('index.php?option=com_volunteers&view=reports&filter_category=d.' . $this->item->id . '&format=feed&type=rss') ?>">
                <span class="icon-feed" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_RSSFEED') ?>
            </a>
        <?php else : ?>
            <div class="row">
                <p class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_NOTE_NO_REPORTS') ?>
                </p>
            </div>
        <?php endif;
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewreports', Text::_('COM_VOLUNTEERS_TAB_CONTACT'));
        ?>
        <?php if ($this->user->guest) : ?>
            <p class="alert alert-info">
                <?php echo Text::_('COM_VOLUNTEERS_NOTE_LOGIN_CONTACT_DEPARTMENT') ?>
            </p>
        <?php else : ?>
            <form class="form" name="sendmail" action="<?php echo Route::_('index.php') ?>" method="post"
                enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label" for="to_name"><?php echo Text::_('COM_VOLUNTEERS_MESSAGE_TO') ?></label>
                    <div>
                        <input type="text" name="to_name" id="to_name" value="<?php echo $this->item->title ?>"
                            class="form-control" disabled="disabled" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="from_name"><?php echo Text::_('COM_VOLUNTEERS_MESSAGE_FROM') ?></label>
                    <div>
                        <input type="text" name="from_name" id="from_name"
                            value="<?php echo ($this->user->name); ?> <<?php echo ($this->user->email); ?>>"
                            class="form-control" disabled="disabled" />
                    </div>
                </div>
                <div class="mb-3">
                    <div>
                        <input type="text" name="subject" id="subject" class="form-control"
                            placeholder="<?php echo Text::_('COM_VOLUNTEERS_MESSAGE_SUBJECT') ?>" required />
                    </div>
                </div>
                <div class="mb-3">
                    <textarea rows="10" name="message" id="message" class="form-control"
                        placeholder="<?php echo Text::sprintf('COM_VOLUNTEERS_MESSAGE_BODY', $this->item->title) ?>"
                        required></textarea>
                </div>
                <div class="alert alert-info">
                    <?php echo Text::sprintf('COM_VOLUNTEERS_MESSAGE_NOTICE', $this->item->title) ?>
                </div>
                <div class="mb-3">
                    <input type="submit" value="<?php echo Text::_('COM_VOLUNTEERS_MESSAGE_SUBMIT') ?>" name="submit"
                        id="submitButton" class="btn btn-success float-end" />
                </div>

                <input type="hidden" name="option" value="com_volunteers" />
                <input type="hidden" name="task" value="department.sendmail" />
                <?php echo HtmlHelper::_('form.token'); ?>
            </form>
        <?php endif;
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.endTabSet');
        ?>
    </div>
</div>
