<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Volunteers\Administrator\View\Members\HtmlView;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect')
    ->addInlineScript(<<<'JS'
// The export is a download that keeps the page open, clear its task so the next search or filter does not export again
const membersSubmitbutton = Joomla.submitbutton;

Joomla.submitbutton = (task) => {
    membersSubmitbutton(task);

    if (task === 'members.export') {
        document.getElementById('adminForm').task.value = '';
    }
};
JS, [], [], ['core']);

$user        = $this->getCurrentUser();
$userId      = $user->id;
$listOrder   = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
$canEdit     = $user->authorise('core.edit', 'com_volunteers');
$teamStatus  = [
    0 => 'COM_VOLUNTEERS_FIELD_STATUS_INFORMATION',
    1 => 'COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL',
    2 => 'COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL',
];
?>
<form action="<?php echo Route::_('index.php?option=com_volunteers&view=members'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="memberList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_VOLUNTEERS_MEMBERS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_POSITION', 'position_title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DEPARTMENT', 'department_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAM', 'team_title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_VOLUNTEER', 'volunteer_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DATE_STARTED', 'a.date_started', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DATE_ENDED', 'a.date_ended', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) :
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || !$item->checked_out;
                            $canChange  = $user->authorise('core.edit.state', 'com_volunteers') && $canCheckin;

                            // A member without a position still needs a title to link to the member form
                            $positionTitle = $item->position_title ?: Text::_('COM_VOLUNTEERS_NO_POSITION');
                            $department    = $item->department_title ?: $item->teamdepartment_title;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->volunteer_name); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'members.', $canChange, 'cb'); ?>
                                </td>
                                <th scope="row" class="has-context">
                                    <div>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'members.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_volunteers&task=member.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($positionTitle); ?>">
                                                <?php echo $this->escape($positionTitle); ?></a>
                                        <?php else : ?>
                                            <?php echo $this->escape($positionTitle); ?>
                                        <?php endif; ?>
                                        <?php if ($item->role_title) : ?>
                                            <div class="small"><?php echo $this->escape($item->role_title); ?></div>
                                        <?php endif; ?>
                                        <div class="small d-md-none">
                                            <?php echo $this->escape(implode(', ', array_filter([$item->volunteer_name, $item->team_title, $department]))); ?>
                                        </div>
                                    </div>
                                </th>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo $this->escape($department); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php if ($item->team_title) : ?>
                                        <?php echo $this->escape($item->team_title); ?>
                                        <?php if (isset($teamStatus[(int) $item->team_status])) : ?>
                                            <div><?php echo Text::_($teamStatus[(int) $item->team_status]); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . (int) $item->volunteer); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->volunteer_name); ?>">
                                            <?php echo $this->escape($item->volunteer_name); ?></a>
                                    <?php else : ?>
                                        <?php echo $this->escape($item->volunteer_name); ?>
                                    <?php endif; ?>
                                    <div><?php echo $this->escape($item->user_email); ?></div>
                                </td>
                                <?php // A member who has not stepped down has a zero end date, which is shown empty ?>
                                <?php foreach (['date_started', 'date_ended'] as $field) : ?>
                                    <td class="small d-none d-lg-table-cell text-nowrap">
                                        <?php echo $item->$field && $item->$field !== '0000-00-00' ? $this->escape($item->$field) : ''; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <?php echo $this->filterForm->renderControlFields(); ?>
            </div>
        </div>
    </div>
</form>
