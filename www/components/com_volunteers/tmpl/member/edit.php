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
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;
use Joomla\Component\Volunteers\Site\View\Member\HtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var HtmlView $this */
// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
$wa->useScript('keepalive');
$wa->useScript('com_volunteers.form-edit');

if ($this->item->department) {
    $view = 'department';
    $id = $this->item->department;
} elseif ($this->item->team) {
    $view = 'team';
    $id = $this->item->team;
}

?>

<div class="member-edit">

    <form id="member"
        action="<?php echo Route::_('index.php?option=com_volunteers&task=member.save&id=' . (int) $this->item->id); ?>"
        method="post" class="form-validate" enctype="multipart/form-data">
        <div class="filter-bar clearfix">
            <div class="btn-toolbar float-end gap-2">
                <div id="toolbar-cancel" class="btn-group">
                    <button class="btn btn-danger" type="button" data-task="member.cancel">
                        <span class="icon-cancel" aria-hidden="true"></span>
                        <?php echo Text::_('JCANCEL') ?>
                    </button>
                </div>
                <div id="toolbar-apply" class="btn-group">
                    <button class="btn btn-success" type="button" data-task="member.save">
                        <span class="icon-pencil" aria-hidden="true"></span>
                        <?php echo Text::_('JSAVE') ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="border-bottom pb-2 mb-3">
            <h1 class="vol_h1">
                <?php echo Text::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?>
            </h1>
        </div>

        <?php if ($this->item->department) : ?>
            <?php echo $this->form->renderField('department'); ?>
        <?php endif; ?>
        <?php if ($this->item->team) : ?>
            <?php echo $this->form->renderField('team'); ?>
        <?php endif; ?>

        <?php echo $this->form->renderField('volunteer'); ?>

        <hr>

        <?php echo $this->form->renderField('position'); ?>
        <?php echo $this->form->renderField('role'); ?>

        <hr>

        <?php echo $this->form->renderField('date_started'); ?>

        <div class="control-group">
            <div class="control-label">
                <?php echo $this->form->getLabel('date_ended'); ?>
            </div>
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_ENDED_MEMBER_DESC') ?>
                </div>
                <?php echo $this->form->getInput('date_ended'); ?>
            </div>
        </div>

        <hr>

        <div class="filter-bar clearfix">
            <div class="btn-toolbar float-end gap-2">
                <div id="toolbar-cancel" class="btn-group">
                    <button class="btn btn-danger" type="button" data-task="member.cancel">
                        <span class="icon-cancel" aria-hidden="true"></span>
                        <?php echo Text::_('JCANCEL') ?>
                    </button>
                </div>
                <div id="toolbar-apply" class="btn-group">
                    <button class="btn btn-success" type="button" data-task="member.save">
                        <span class="icon-pencil" aria-hidden="true"></span>
                        <?php echo Text::_('JSAVE') ?>
                    </button>
                </div>
            </div>
        </div>

        <input type="hidden" name="option" value="com_volunteers" />
        <input type="hidden" name="task" value="member.save" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
