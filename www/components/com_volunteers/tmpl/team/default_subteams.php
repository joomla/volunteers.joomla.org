<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Team\HtmlView $this */
?>
<?php if ($this->acl->create_team) { ?>
    <div class="row">
    <div class="filter-bar">
        <a class="btn btn-light float-end"
            href="<?php echo Route::_('index.php?option=com_volunteers&task=team.add&team=' . $this->item->id) ?>">
            <span class="icon-new" aria-hidden="true"></span>
            <?php echo Text::_('COM_VOLUNTEERS_SUBTEAM_ADD') ?>
        </a>
    </div>
    </div>
    <hr>
<?php } ?>
<?php foreach ($this->item->subteams as $i => $item) { ?>
    <div class="row">
        <div class="team card card-body mb-3 team-<?php echo ($item->id); ?>">
            <div class="row">
                <div class="col-8">
                    <h2 class="vol_h2" style="margin-top: 0;">
                        <a
                            href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>">
                            <?php echo ($item->title); ?>

                            <?php if ($item->acronym) {
                                ?> (<?php echo ($item->acronym) ?>)<?php
                            } ?>
                        </a>
                        <?php if ($item->date_ended != '0000-00-00') { ?>
                            <small>
                                <?php echo Text::_('COM_VOLUNTEERS_ARCHIVED') ?>
                            </small>
                        <?php } ?>
                    </h2>
                    <p>
                        <?php echo ($item->description); ?>
                    </p>
                    <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>"
                        class="btn btn-light">
                        <span class="icon-chevron-right" aria-hidden="true"></span>
                        <?php echo Text::_('COM_VOLUNTEERS_READ_MORE') . ' ' . $item->title; ?>
                    </a>
                </div>
                <div class="col-4">
                    <div class="members">
                        <?php $i = 0;
                        if (!empty($item->members)) {
                            foreach ($item->members as $member) { ?>
                                <a class="tip hasTooltip" title="<?php echo $member->volunteer_name; ?>"
                                    href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $member->volunteer) ?>">
                                    <?php echo VolunteersHelper::image($member->volunteer_image, 'small', false, $member->volunteer_image); ?>
                                </a>
                                <?php $i++;
                                if ($i == 14) {
                                    break;
                                }
                            }
                        }
                        if (!empty($item->members) && count($item->members) > 14) {
                            ?>
                            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>"
                                class="all-members">
                                <span class="all">
                                    <?php echo Text::_('COM_VOLUNTEERS_ALL') ?>
                                </span><span class="number"><?php echo (' ' . count($item->members)); ?></span>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }
