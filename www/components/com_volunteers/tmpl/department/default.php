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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Department\HtmlView $this */

// Import CSS
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
        <?php if ($this->acl->edit) : ?>
            <a class="btn btn-light float-end" href="<?php echo Route::_('index.php?option=com_volunteers&task=department.edit&id=' . $this->item->id) ?>">

<span class="icon-edit" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS_EDIT') ?>
            </a>
        <?php endif; ?>
    </div>
    <div class="border-bottom pb-2 mb-3">
        <h1 class="vol_h1">
            <?php echo $this->escape($this->item->title) ?>
        </h1>
    </div>

    <p class="lead">
        <?php echo strip_tags((string) $this->item->description) ?>
    </p>

    <dl class="row">
        <?php if ($this->item->website) : ?>
            <dt class="col-sm-auto">
                <?php echo Text::_('COM_VOLUNTEERS_FIELD_WEBSITE') ?>
            </dt>
            <dd class="col-sm"><a href="<?php echo ($this->item->website) ?>"><?php echo ($this->item->website) ?></a></dd>
        <?php endif; ?>
    </dl>
</div>

<div class="row">
    <div class="col-12">
        <?php
        echo HTMLHelper::_('uitab.startTabSet', 'departmentTab', ['active' => 'viewmembers', 'recall' => true, 'breakpoint' => 768]);

        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewmembers', Text::_('COM_VOLUNTEERS_TAB_COORDINATORS'));
        echo $this->loadTemplate('members');
        echo HTMLHelper::_('uitab.endTab');

        if ($this->item->members->honorroll) {
            echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewhonourroll', Text::_('COM_VOLUNTEERS_TAB_HONORROLL'));
            echo $this->loadTemplate('honourroll');
            echo HTMLHelper::_('uitab.endTab');
        }

        if ($this->item->teams) {
            echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewteams', Text::_('COM_VOLUNTEERS_TAB_DEPARTMENTTEAMS'));
            echo $this->loadTemplate('teams');
            echo HTMLHelper::_('uitab.endTab');
        }
        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewreports', Text::_('COM_VOLUNTEERS_TAB_REPORTS_DEPARTMENT'));
        echo $this->loadTemplate('reports');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewreportsteams', Text::_('COM_VOLUNTEERS_TAB_REPORTS_TEAM'));
        echo $this->loadTemplate('reportsteams');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'departmentTab', 'viewcontact', Text::_('COM_VOLUNTEERS_TAB_CONTACT'));
        echo $this->loadTemplate('contact');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.endTabSet');

        ?>
    </div>
</div>
