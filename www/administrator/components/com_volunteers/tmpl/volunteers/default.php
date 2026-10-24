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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper;
use Joomla\Component\Volunteers\Administrator\View\Volunteers\HtmlView;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect')
    ->addInlineStyle('.volunteer-image img {width: 50px; height: 50px; object-fit: cover;}');

$user        = $this->getCurrentUser();
$userId      = $user->id;
$listOrder   = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
$canEdit     = $user->authorise('core.edit', 'com_volunteers');
$canEditUser = $user->authorise('core.manage', 'com_users') && $user->authorise('core.edit', 'com_users');
$noImage     = Uri::root() . 'media/com_volunteers/images/joomlaperson.png';
$nullDate    = '0000-00-00 00:00:00';
?>
<form action="<?php echo Route::_('index.php?option=com_volunteers&view=volunteers'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table" id="volunteerList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_VOLUNTEERS_VOLUNTEERS_TABLE_CAPTION'); ?>,
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
                                <th scope="col" class="w-1 d-none d-md-table-cell">
                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_IMAGE'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_VOLUNTEER', 'user.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-25 d-none d-xl-table-cell">
                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_INTRO'); ?>
                                </th>
                                <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAMS_NUM', 'num_teams', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_SPAM', 'a.spam', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_CREATED_DATE', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_MODIFIED_DATE', 'a.modified', $listDirn, $listOrder); ?>
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
                            $location   = array_filter([$item->city, VolunteersHelper::$countries[$item->country] ?? $item->country]);
                            $birthday   = $item->birthday && $item->birthday !== '0000-00-00' ? substr($item->birthday, 5) : '';
                            // A Joomler without a linked user has no name, the alias keeps the edit link usable
                            $name       = $item->name ?: $item->alias;
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $name); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'volunteers.', $canChange, 'cb'); ?>
                                </td>
                                <td class="volunteer-image d-none d-md-table-cell">
                                    <img class="rounded" src="<?php echo $this->escape($item->image ?: $noImage); ?>" alt="" loading="lazy">
                                </td>
                                <th scope="row" class="has-context">
                                    <div>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'volunteers.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($name); ?>">
                                                <?php echo $this->escape($name); ?></a>
                                        <?php else : ?>
                                            <?php echo $this->escape($name); ?>
                                        <?php endif; ?>
                                        <div class="small">
                                            <span class="icon-user" aria-hidden="true"></span>
                                            <?php if ($canEditUser) : ?>
                                                <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_id); ?>">
                                                    <?php echo $this->escape($item->user_username); ?></a>
                                            <?php else : ?>
                                                <?php echo $this->escape($item->user_username); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small">
                                            <span class="icon-mail" aria-hidden="true"></span>
                                            <?php echo $this->escape($item->user_email); ?>
                                        </div>
                                        <?php if ($location) : ?>
                                            <div class="small">
                                                <span class="icon-location" aria-hidden="true"></span>
                                                <?php echo $this->escape(implode(', ', $location)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($birthday) : ?>
                                            <div class="small">
                                                <span class="icon-calendar" aria-hidden="true"></span>
                                                <?php echo $this->escape($birthday); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </th>
                                <td class="small d-none d-xl-table-cell">
                                    <?php echo $this->escape(HTMLHelper::_('string.truncate', strip_tags((string) $item->intro), 250)); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <a href="<?php echo Route::_('index.php?option=com_volunteers&view=members&filter[volunteer]=' . (int) $item->id); ?>">
                                        <?php echo (int) $item->num_teams; ?></a>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php echo (int) $item->spam; ?>
                                </td>
                                <td class="small d-none d-lg-table-cell">
                                    <?php echo $item->created && $item->created !== $nullDate ? HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')) : ''; ?>
                                </td>
                                <td class="small d-none d-lg-table-cell">
                                    <?php echo $item->modified && $item->modified !== $nullDate ? HTMLHelper::_('date', $item->modified, Text::_('DATE_FORMAT_LC4')) : ''; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo (int) $item->id; ?>
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
