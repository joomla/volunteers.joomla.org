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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

/** @var \Joomla\Component\Volunteers\Site\View\Teams\HtmlView $this */

$active = $this->state->get('filter.active', 1);


// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}


?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="filter-bar d-flex flex-wrap justify-content-end gap-2">
            <div class="input-group w-auto">
                <label class="visually-hidden" for="filter-search"><?php echo Text::_('COM_VOLUNTEERS_SEARCH_TEAM'); ?></label>
                <input type="search" name="filter_search" id="filter-search" class="form-control"
                    value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                    placeholder="<?php echo Text::_('COM_VOLUNTEERS_SEARCH_TEAM'); ?>">
                <button class="btn btn-primary" type="submit" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
                    <span class="icon-search" aria-hidden="true"></span>
                </button>
                <?php if ($this->state->get('filter.search')) : ?>
                    <button class="btn btn-light" type="button" aria-label="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
                        onclick="this.form.filter_search.value='';this.form.submit();">
                        <span class="icon-times" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="btn-group" role="group" aria-label="<?php echo Text::_('JSTATUS'); ?>">
                <input type="radio" class="btn-check" name="filter_active" id="filter_active1" value="1"
                    autocomplete="off" onchange="this.form.submit();"<?php echo $active == 1 ? ' checked' : ''; ?>>
                <label class="btn btn-outline-success" for="filter_active1"><?php echo Text::_('COM_VOLUNTEERS_ACTIVE'); ?></label>
                <input type="radio" class="btn-check" name="filter_active" id="filter_active0" value="0"
                    autocomplete="off" onchange="this.form.submit();"<?php echo $active == 0 ? ' checked' : ''; ?>>
                <label class="btn btn-outline-danger" for="filter_active0"><?php echo Text::_('COM_VOLUNTEERS_ARCHIVED'); ?></label>
                <input type="radio" class="btn-check" name="filter_active" id="filter_active2" value="2"
                    autocomplete="off" onchange="this.form.submit();"<?php echo $active == 2 ? ' checked' : ''; ?>>
                <label class="btn btn-outline-dark" for="filter_active2"><?php echo Text::_('COM_VOLUNTEERS_ALL'); ?></label>
            </div>
        </div>
        <div class="border-bottom pb-2 mb-3">
            <?php if ($this->state->get('filter.groups')) : ?>
                <h1 class="vol_h1"><?php echo Text::_('COM_VOLUNTEERS_TITLE_GROUPS') ?></h1>
            <?php else : ?>
                <h1 class="vol_h1"><?php echo Text::_('COM_VOLUNTEERS_TITLE_TEAMS') ?></h1>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($this->items)) {
        foreach ($this->items as $i => $item) : ?>
        <div class="row">
            <div class="team card card-body mb-3 team-<?php echo($item->id); ?>">
                <div class="row">
                    <div class="col-8">
                        <h2 class="vol_h2" style="margin-top: 0;">
                            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>">
                                        <?php echo($item->title); ?><?php if ($item->acronym) :
                                            ?> (<?php echo($item->acronym) ?>)<?php
                                        endif; ?>
                            </a>
                                    <?php if ($item->date_ended != '0000-00-00') : ?>
                                <small><?php echo Text::_('COM_VOLUNTEERS_ARCHIVED') ?></small>
                                    <?php endif; ?>
                            <span class="badge text-bg-info">
                                        <?php if ($item->status == '0') : ?>
                                            <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_INFORMATION') ?>
                                        <?php elseif ($item->status == '1') : ?>
                                            <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL') ?>
                                        <?php elseif ($item->status == '2') : ?>
                                            <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL') ?>
                                        <?php endif; ?>
                            </span>
                        </h2>
                        <p><?php echo($item->description); ?></p>

                                <?php if (count($item->subteams)) : ?>
                            <h3 class="vol_h3"><?php echo Text::_('COM_VOLUNTEERS_SUBTEAMS') ?></h3>
                            <ul class="nav flex-column">
                                    <?php foreach ($item->subteams as $subteam) : ?>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $subteam->id) ?>">
                                            <?php echo($subteam->title); ?><?php if ($subteam->acronym) :
                                                ?> (<?php echo($subteam->acronym) ?>)<?php
                                            endif; ?>
                                            <span class="badge text-bg-info">
                                                <?php if ($subteam->status == '0') : ?>
                                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_INFORMATION') ?>
                                                <?php elseif ($subteam->status == '1') : ?>
                                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL') ?>
                                                <?php elseif ($subteam->status == '2') : ?>
                                                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL') ?>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                            </ul>
                                <?php endif; ?>
                        <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>" class="btn btn-light">
                            <span class="icon-chevron-right" aria-hidden="true"></span><?php echo Text::_('COM_VOLUNTEERS_READ_MORE') . ' ' . $item->title; ?>
                        </a>
                    </div>
                    <div class="col-4">
                        <div class="members">
                                    <?php $i = 0; ?>
                                    <?php if (!empty($item->members)) {
                                        foreach ($item->members as $member) : ?>
                                <a class="tip hasTooltip" title="<?php echo $member->volunteer_name; ?>" href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $member->volunteer) ?>">
                                                                            <?php echo VolunteersHelper::image($member->volunteer_image, 'small', false, $member->volunteer_name); ?>
                                </a>
                                                                            <?php $i++;
                                                                            if ($i == 14) {
                                                                                break;
                                                                            } ?>
                                        <?php endforeach;
                                    } ?>
                            <?php if (count($item->members) > 14) : ?>
                                <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>" class="all-members">
                                    <span class="all"><?php echo Text::_('COM_VOLUNTEERS_ALL') ?></span><span class="number"><?php echo(' ' . count($item->members)); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach;
    } ?>

    <div class="pagination">
        <p class="counter float-end">
            <?php echo $this->pagination->getPagesCounter(); ?>
        </p>

        <?php echo $this->pagination->getPagesLinks(); ?>
    </div>
</form>
