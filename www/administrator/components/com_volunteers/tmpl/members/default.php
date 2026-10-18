<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
/** @var $this HtmlView */

$user = $this->getCurrentUser();
$userId = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$canOrder = $user->authorise('core.edit.state', 'com_volunteers');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder) {
    $saveOrderingUrl = 'index.php?option=com_volunteers&task=members.saveOrderAjax&tmpl=component';
    HTMLHelper::_('draggablelist.draggable');
}

$wa = $this->getDocument()->
getWebAssetManager();
$wa->useScript('table.columns');
?>

<form action="<?php echo Route::_('index.php?option=com_volunteers&view=members'); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container" class="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        <?php if (empty($this->items)) :
            ?>
                <div class="alert alert-info">
                    <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
                <?php
        else :
            ?>
                <h3><?php echo count($this->items); ?> members (matching filters)</h3>
                <table class="table table-striped" id="itemsList">
                    <thead>
                    <tr>
                        <th width="6%"></th>
                        <th width="15%" class="team">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DEPARTMENT', 'a.department', $listDirn, $listOrder); ?>
                        </th>
                        <th width="15%" class="team">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAM', 'a.team', $listDirn, $listOrder); ?>
                        </th>
                        <th>
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_POSITION', 'a.position', $listDirn, $listOrder); ?>
                        </th>
                        <th width="15%" class="name">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_VOLUNTEER', 'a.volunteer', $listDirn, $listOrder); ?>
                        </th>
                        <th width="8%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DATE_STARTED', 'a.date_started', $listDirn, $listOrder); ?>
                        </th>
                        <th width="8%">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DATE_ENDED', 'a.date_ended', $listDirn, $listOrder); ?>
                        </th>
                        <th width="1%" class="text-nowrap text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody <?php if ($saveOrder) :
                        ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>"
                        data-direction="<?php echo strtolower((string) $listDirn); ?>" data-nested="true" <?php
                           endif; ?>>
                    <?php foreach ($this->items as $i => $item) :
                        $ordering = ($listOrder == 'a.ordering');
                        $canCreate = $user->authorise('core.create', 'com_volunteers');
                        $canEdit = $user->authorise('core.edit', 'com_volunteers');
                        $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0;
                        $canChange = $user->authorise('core.edit.state', 'com_volunteers') && $canCheckin;
                        ?>
                            <tr class="row<?php echo $i % 2; ?><?php if ($item->date_ended == '0000-00-00') :
                                ?> success<?php
                                          else :
                                                ?> error<?php
                                          endif; ?>" sortable-volunteer-id="<?php echo $item->id ?>">
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="<?php echo Route::_('index.php?option=com_volunteers&task=member.edit&id=' . (int) $item->id); ?>">
                                        <span class="icon-edit" aria-hidden="true"></span>Edit
                                    </a>
                                </td>
                                <td class="text-nowrap">
                                    <?php echo $item->department_title; ?>
                                    <?php echo $item->teamdepartment_title; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($item->team_title) :
                                        ?>
                                            <?php echo $item->team_title; ?>
                                            <br><small>
                                                <?php if ($item->team_status == 0) :
                                                    ?>
                                                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_INFORMATION'); ?>
                                                        <?php
                                                elseif ($item->team_status == 1) :
                                                    ?>
                                                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL'); ?>
                                                        <?php
                                                elseif ($item->team_status == 2) :
                                                    ?>
                                                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL'); ?>
                                                        <?php
                                                endif; ?>
                                            </small>
                                            <?php
                                    else :
                                        ?>
                                            -
                                            <?php
                                    endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php echo $item->position_title; ?>
                                    <?php if ($item->role_title) :
                                        ?>
                                            <br><small><?php echo $item->role_title; ?></small>
                                            <?php
                                    endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($canEdit) :
                                        ?>
                                            <a href="<?php echo Route::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . (int) $item->volunteer); ?>">
                                                <?php echo $this->escape($item->volunteer_name); ?>
                                            </a>
                                            <?php
                                    else :
                                        ?>
                                            <?php echo $this->escape($item->volunteer_name); ?>
                                            <?php
                                    endif; ?>
                                    <br><small><?php echo $item->user_email; ?></small>
                                </td>
                                <td>
                                    <?php echo $item->date_started; ?>
                                </td>
                                <td>
                                    <?php echo $item->date_ended; ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                            <?php
                    endforeach; ?>
                    </tbody>
                </table>

                <?php echo $this->pagination->getListFooter(); ?>
                <?php
        endif; ?>

        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
