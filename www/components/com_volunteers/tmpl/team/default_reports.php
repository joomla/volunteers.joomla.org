<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Team\HtmlView $this */
?>
<?php if ($this->acl->create_report) : ?>
    <div class="row">
        <div class="filter-bar">
        <a class="btn btn-light float-end" href="<?php echo Route::_('index.php?option=com_volunteers&task=report.add&team=' . $this->item->id) ?>">
            <span class="icon-new" aria-hidden="true"></span>
            <?php echo Text::_('COM_VOLUNTEERS_REPORT_ADD') ?>
        </a>
        </div>
    </div>
    <hr>
<?php endif; ?>
<?php if ($this->item->reports) : ?>
    <?php foreach ($this->item->reports as $report) : ?>
        <div class="row report">
            <div class="col-2 volunteer-image">
                <a
                    href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $report->volunteer_id) ?>">
                        <?php echo VolunteersHelper::image($report->volunteer_image, 'large', false, $report->volunteer_name ?? ''); ?>
                </a>
            </div>
            <div class="col-10">
                <?php if ($this->acl->edit || ($this->user->id == $report->created_by)) : ?>
                    <a class="btn btn-light btn-sm float-end"
                        href="<?php echo Route::_('index.php?option=com_volunteers&task=report.edit&id=' . $report->id) ?>">
                        <span class="icon-edit" aria-hidden="true"></span>
                        <?php echo Text::_('COM_VOLUNTEERS_EDIT') ?>
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
                    <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $report->team) ?>"><?php echo $report->team_title; ?></a>
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
        <hr>
    <?php endforeach; ?>
    <?php if (count($this->item->reports) == 10) : ?>
        <a href="<?php echo Route::_('index.php?option=com_volunteers&view=reports') ?>?filter_category=t.<?php echo $this->item->id; ?>"
            class="btn btn-light">
            <span class="icon-chevron-right"></span>
            <?php echo Text::_('COM_VOLUNTEERS_REPORTS_BROWSE') ?>&nbsp
        </a>
    <?php endif; ?>
    <a class="btn btn-warning float-end"
        href="<?php echo Route::_('index.php?option=com_volunteers&view=reports&filter_category=t.' . $this->item->id . '&format=feed&type=rss') ?>">
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
