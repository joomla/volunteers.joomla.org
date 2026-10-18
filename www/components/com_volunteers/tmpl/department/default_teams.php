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

/** @var \Joomla\Component\Volunteers\Site\View\Department\HtmlView $this */
?>

<div class="tab-pane" id="teams">
    <?php if ($this->acl->create_team) : ?>
        <div class="row">
            <a href="<?php echo Route::_('index.php?option=com_volunteers&task=team.add&department=' . $this->item->id) ?>">
                <button class="vol-button-admin" role="button">
                <span class="icon-new" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_TEAM_ADD') ?></button>
            </a>
        </div>
        <hr>
    <?php endif; ?>
    <table class="table table-striped table-hover table-vertical-align">
        <thead>
        <th>
            <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM') ?>
        </th>
        <th width="20%">
            <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM_LEADER') ?>
        </th>
        <th width="20%">
            <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM_ASSISTENTLEADER') ?>
        </th>
        </thead>
        <tbody>
        <?php foreach ($this->item->teams as $team) : ?>
            <tr>
                <td>
                    <a
                        href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $team->id) ?>">
                        <?php echo ($team->title) ?>
                    </a>
                </td>
                <td>
                    <?php if (!empty($team->leader)) {
                        foreach ($team->leader as $volunteer) : ?>
                            <a class="tip hasTooltip" title="<?php echo $volunteer->volunteer_name; ?>"
                               href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer) ?>">
                                <?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_name ?? ''); ?>
                            </a>
                        <?php endforeach;
                    }
                    ?>
                </td>
                <td>
                    <?php if (!empty($team->assistantleader)) {
                        foreach ($team->assistantleader as $volunteer) : ?>
                            <a class="tip hasTooltip" title="<?php echo $volunteer->volunteer_name; ?>"
                               href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer) ?>">
                                <?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_name ?? ''); ?>
                            </a>
                        <?php endforeach;
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

