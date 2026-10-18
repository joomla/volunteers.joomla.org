<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper;

$wa = $this->getDocument()->
getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect')
    ->addInlineStyle(".volunteer-image img {width: 75px; height: 75px;}");

/** @var $this HtmlView */

$user = $this->getCurrentUser();
$userId = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$canOrder = $user->authorise('core.edit.state', 'com_volunteers');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder) {
    $saveOrderingUrl = 'index.php?option=com_volunteers&task=volunteers.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo Route::_('index.php?option=com_volunteers&view=volunteers'); ?>" method="post" name="adminForm"
    id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden">
                            <?php echo Text::_('INFO'); ?>
                        </span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <h3>
                        <?php echo count($this->items); ?> volunteers (matching filters)
                    </h3>
                    <table class="table" id="itemsList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_VOLUNTEERS_TEAMS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy">
                                <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
                            </span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" width="75">
                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_IMAGE'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_VOLUNTEER', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                <td style="min-width:200px">
                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_INTRO'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAMS_NUM', 'num_teams', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_SPAM', 'a.spam', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_CREATED_DATE', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_MODIFIED_DATE', 'a.modified', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
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
                                <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->id ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->user_username); ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php
                                        $iconClass = '';
                                        if (!$canChange) {
                                            $iconClass = ' inactive';
                                        } elseif (!$saveOrder) {
                                            $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                        }
                                        ?>
                                        <span class="sortable-handler<?php echo $iconClass; ?>">
                                            <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                        </span>
                                        <?php if ($canChange && $saveOrder) : ?>
                                            <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
                                                class="width-20 text-area-order hidden" />
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'volunteers.', $canChange, 'cb'); ?>
                                    </td>
                                    <td class="volunteer-image">
                                        <?php if ($item->image) : ?>
                                            <img class="rounded" src="<?php echo $item->image; ?>"
                                                alt="<?php echo $this->escape($item->user_username); ?>">
                                        <?php else : ?>
                                            <img class="rounded"
                                                src="<?php echo URI::root() . 'media/com_volunteers/images/joomlaperson.png'; ?>"
                                                alt="">
                                        <?php endif; ?>
                                    </td>
                                    <th scope="row" class="has-context">
                                        <div>
                                            <?php if ($item->checked_out) : ?>
                                                <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'volunteers.', $canCheckin); ?>
                                            <?php endif; ?>
                                            <?php if ($canEdit) : ?>
                                                <a href="<?php echo Route::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . (int) $item->id); ?>"
                                                    title="<?php echo Text::_('JACTION_EDIT'); ?>">
                                                    <?php echo $this->escape($item->name); ?></a>
                                            <?php else : ?>
                                                <?php echo $this->escape($item->name); ?>
                                            <?php endif; ?>
                                            <div class="small">
                                                <span class="icon-user" aria-hidden="true"></span>
                                                <?php if ($canEdit) : ?>
                                                    <a
                                                        href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_id); ?>">
                                                        <?php echo $this->escape($item->user_username); ?></a>
                                                <?php else : ?>
                                                    <?php echo $this->escape($item->user_username); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small">
                                                <span class="icon-mail" aria-hidden="true"></span>
                                                <?php echo $this->escape($item->user_email); ?>
                                            </div>
                                            <div class="small">
                                                <span class="icon-location" aria-hidden="true"></span>
                                                <?php echo $item->city; ?>,
                                                <?php if ($item->country) : ?>
                                                    <?php echo VolunteersHelper::$countries[$item->country]; ?>
                                                <?php else : ?>
                                                    <?php echo $item->country; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small">
                                                <span class="icon-calendar-3" aria-hidden="true"></span>
                                                <?php echo $item->birthday; ?>
                                            </div>
                                    </th>
                                    <td>
                                        <?php echo HTMLHelper::_('string.truncate', $item->intro, 250); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <a
                                            href="<?php echo Route::_('index.php?option=com_volunteers&view=members&filter[volunteer]=' . $item->id); ?>">
                                            <?php echo $item->num_teams; ?>
                                        </a>
                                    </td>
                                    <td class="d-none d-md-table-cell text-center">
                                        <?php echo $item->spam; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('date', $item->modified, Text::_('DATE_FORMAT_LC4')); ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="" />
                <input type="hidden" name="boxchecked" value="0" />
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
