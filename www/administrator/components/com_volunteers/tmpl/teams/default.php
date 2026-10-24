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
use Joomla\Component\Volunteers\Administrator\View\Teams\HtmlView;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user      = $this->getCurrentUser();
$userId    = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canEdit   = $user->authorise('core.edit', 'com_volunteers');

$teamStatus = [
    0 => 'COM_VOLUNTEERS_FIELD_STATUS_INFORMATION',
    1 => 'COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL',
    2 => 'COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL',
];
?>
<form action="<?php echo Route::_('index.php?option=com_volunteers&view=teams'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table" id="teamList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_VOLUNTEERS_TEAMS_TABLE_CAPTION'); ?>,
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
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DEPARTMENT', 'department.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAMSTATUS', 'a.status', $listDirn, $listOrder); ?>
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
                        <?php // The model keys the teams by id, the row number drives the checkboxes ?>
                        <?php foreach (array_values($this->items) as $i => $item) :
                            $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || !$item->checked_out;
                            $canChange  = $user->authorise('core.edit.state', 'com_volunteers') && $canCheckin;
                            $ended      = $item->date_ended && $item->date_ended !== '0000-00-00';
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'teams.', $canChange, 'cb'); ?>
                                </td>
                                <th scope="row" class="has-context">
                                    <div>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'teams.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_volunteers&task=team.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>">
                                                <?php echo $this->escape($item->title); ?></a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif; ?>
                                        <?php if ($ended) : ?>
                                            <span class="badge bg-secondary"><?php echo Text::_('COM_VOLUNTEERS_ARCHIVED'); ?></span>
                                        <?php endif; ?>
                                        <div class="small">
                                            <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                                        </div>
                                        <?php if ($item->parent_id) : ?>
                                            <div class="small">
                                                <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM_PARENT') . ': ' . $this->escape($item->parent_title); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small d-md-none">
                                            <?php echo Text::_('COM_VOLUNTEERS_FIELD_DEPARTMENT') . ': ' . $this->escape($item->department_title); ?>
                                        </div>
                                    </div>
                                </th>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $this->escape($item->department_title); ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?php echo isset($teamStatus[$item->status]) ? Text::_($teamStatus[$item->status]) : ''; ?>
                                </td>
                                <td class="small d-none d-lg-table-cell">
                                    <?php echo $ended ? HTMLHelper::_('date', $item->date_ended, Text::_('DATE_FORMAT_LC4'), null) : ''; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php // Load the pagination. ?>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <?php echo $this->filterForm->renderControlFields(); ?>
            </div>
        </div>
    </div>
</form>
