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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;
use Joomla\CMS\Factory;

/** @var \Joomla\Component\Volunteers\Site\View\Departments\HtmlView $this */


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
                <label class="visually-hidden" for="filter-search"><?php echo Text::_('COM_VOLUNTEERS_SEARCH_DEPARTMENT'); ?></label>
                <input type="search" name="filter_search" id="filter-search" class="form-control"
                    value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                    placeholder="<?php echo Text::_('COM_VOLUNTEERS_SEARCH_DEPARTMENT'); ?>">
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
        </div>
        <div class="border-bottom pb-2 mb-3">
            <h1 class="vol_h1"><?php echo Text::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS') ?></h1>
        </div>
    </div>
    <?php if (!empty($this->items)) {
        foreach ($this->items as $i => $item) : ?>
        <div class="row">
            <div class="team card card-body bg-body-tertiary mb-3 team-<?php echo($item->id); ?>">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="vol_h2" style="margin-top: 0;">
                            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=department&id=' . $item->id) ?>">
                                        <?php echo($item->title); ?>
                            </a>
                        </h2>
                        <p><?php echo($item->description); ?></p>
                        <a href="<?php echo Route::_('index.php?option=com_volunteers&view=department&id=' . $item->id) ?>" class="btn btn-light">
                            <span class="icon-chevron-right" aria-hidden="true"></span>
                            <?php echo Text::_('COM_VOLUNTEERS_READ_MORE') . ' ' . $item->title; ?>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <div class="members d-flex flex-wrap mt-3 mt-md-0">
                                    <?php $i = 0; ?>

                                    <?php if (!empty($item->members)) {
                                        foreach ($item->members as $member) : ?>
                                <a class="tip hasTooltip" title="<?php echo $member->volunteer_name; ?>" href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $member->volunteer) ?>">
                                                                            <?php echo VolunteersHelper::image($member->volunteer_image, 'small', false, $member->volunteer_name ?? ''); ?>
                                </a>
                                                                            <?php $i++;
                                                                            if ($i == 14) {
                                                                                break;
                                                                            } ?>
                                        <?php endforeach;
                                    } ?>
                            <?php if (count($item->members) > 14) : ?>
                                <a href="<?php echo Route::_('index.php?option=com_volunteers&view=department&id=' . $item->id) ?>" class="all-members">
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
