<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper as AdminVolunteersHelper;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Team\HtmlView $this */

// Import CSS and set up default tab
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
?>
<div class="row">
    <div class="filter-bar">
        <?php if ($this->acl->edit) { ?>
            <a class="btn btn-light float-end"
                href="<?php echo Route::_('index.php?option=com_volunteers&task=team.edit&id=' . $this->item->id) ?>">
                <span class="icon-edit" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_TITLE_TEAMS_EDIT') ?>
            </a>
        <?php } ?>
    </div>
    <div class="border-bottom pb-2 mb-3">
        <h1 class="vol_h1">
            <?php echo $this->escape($this->item->title) ?>
            <?php if ($this->item->acronym) { ?>
                (<?php echo $this->escape($this->item->acronym) ?>)
            <?php } ?>
            <?php if (!$this->item->active) { ?>
                <span class="badge text-bg-secondary"><?php echo Text::_('COM_VOLUNTEERS_ARCHIVED') ?></span>
            <?php } ?>
            <span class="badge text-bg-primary">
                <?php if ($this->item->status == '0') {
                    echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_INFORMATION');
                } elseif ($this->item->status == '1') {
                    echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL');
                } elseif ($this->item->status == '2') {
                    echo Text::_('COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL');
                } ?>
            </span>
        </h1>
    </div>

    <div class="lead team-page-description">
        <?php echo $this->item->description; ?>
    </div>

    <dl class="row dl-horizontal">
        <?php if ($this->item->website) { ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_WEBSITE') ?>
            </dt>
            <dd class="col-sm"><a href="<?php echo ($this->item->website) ?>"><?php echo ($this->item->website) ?></a></dd>
        <?php } ?>

        <?php $boardTeam = (int) $this->item->department === AdminVolunteersHelper::boardDepartment(); ?>
        <?php if ($this->item->department_title && !$boardTeam) { ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_DEPARTMENT') ?>
            </dt>
            <dd class="col-sm">
                <a
                    href="<?php echo Route::_('index.php?option=com_volunteers&view=department&id=' . $this->item->department); ?>"><?php echo $this->item->department_title; ?></a>
            </dd>
        <?php } ?>

        <?php if ($this->item->parent_id) { ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM_PARENT') ?>
            </dt>
            <dd class="col-sm">
                <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $this->item->parent_id); ?>"><?php echo $this->item->parent_title; ?></a>
            </dd>
        <?php } ?>

        <?php if ($this->item->date_started != '0000-00-00') { ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?>
            </dt>
            <dd class="col-sm">
                <?php echo VolunteersHelper::date($this->item->date_started, 'F Y'); ?>
            </dd>
        <?php } ?>

        <?php if (!$this->item->active) { ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_ENDED') ?>
            </dt>
            <dd class="col-sm">
                <?php echo VolunteersHelper::date($this->item->date_ended, 'F Y'); ?>
            </dd>
        <?php } ?>
    </dl>
</div>

<div class="row">
    <div class="col-12">
        <?php
        echo HTMLHelper::_('uitab.startTabSet', 'teamsTab', ['active' => 'members', 'recall' => true, 'breakpoint' => 768]);
//echo "<pre>";print_r($this->item);echo "</pre>";exit();
        /************************ TAB ***********************************/
        if ($this->item->active) {
            echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'members', Text::_('COM_VOLUNTEERS_TAB_MEMBERS'));
            echo $this->loadTemplate('members');
            echo HTMLHelper::_('uitab.endTab');
        }

        /************************ TAB ***********************************/
        if ($this->item->members->honorroll) {
            echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'honorroll', Text::_('COM_VOLUNTEERS_TAB_HONORROLL'));
            echo $this->loadTemplate('honourroll');
            echo HTMLHelper::_('uitab.endTab');
        }

        /************************ TAB ***********************************/
        if (!$this->item->parent_id && (count($this->item->subteams) > 0 || $this->acl->create_team)) {
            echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'subteams', Text::_('COM_VOLUNTEERS_TAB_SUBTEAMS'));
            echo $this->loadTemplate('subteams');
            echo HTMLHelper::_('uitab.endTab');
        }

        /************************ TAB ***********************************/
        if ($this->item->active) {
            echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'roles', Text::_('COM_VOLUNTEERS_TAB_ROLES'));
            echo $this->loadTemplate('roles');
            echo HTMLHelper::_('uitab.endTab');
        }

        /************************ TAB ***********************************/
        echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'reports', Text::_('COM_VOLUNTEERS_TAB_REPORTS'));
        echo $this->loadTemplate('reports');
        echo HTMLHelper::_('uitab.endTab');

        /************************ TAB ***********************************/
        if ($this->item->active) {
            echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'getinvolved', Text::_('COM_VOLUNTEERS_TAB_GETINVOLVED'));

            if ($this->item->getinvolved) {
                echo $this->item->getinvolved;
            } else {
                echo '<a  class="btn btn-light"  onclick="document.getElementById(\'teamsTab\').activateTab(document.getElementById(\'contact\'));"><span class="icon-chevron-right" aria-hidden="true"></span> ' . Text::_('COM_VOLUNTEERS_USE_CONTACT') . '</a>';
            }

            echo HTMLHelper::_('uitab.endTab');

            /************************ TAB ***********************************/
            echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'contact', Text::_('COM_VOLUNTEERS_TAB_CONTACT'));
            echo $this->loadTemplate('contact');
            echo HTMLHelper::_('uitab.endTab');
        }

        echo HTMLHelper::_('uitab.endTabSet');
        ?>
    </div>
</div>
