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
$wa->useScript('keepalive');
$wa->useScript('com_volunteers.form-edit');
?>

<div class="department-edit">

    <form id="department"
        action="<?php echo Route::_('index.php?option=com_volunteers&task=department.save&id=' . (int) $this->item->id); ?>"
        method="post" class="form-validate" enctype="multipart/form-data">
        <div class="filter-bar clearfix">
            <div class="btn-toolbar float-end gap-2">
                <div id="toolbar-cancel" class="btn-group">
                    <button class="btn btn-danger" type="button" data-task="department.cancel">
                        <span class="icon-cancel" aria-hidden="true"></span>
                        <?php echo Text::_('JCANCEL') ?>
                    </button>
                </div>
                <div id="toolbar-apply" class="btn-group">
                    <button class="btn btn-success" type="button" data-task="department.save">
                        <span class="icon-pencil" aria-hidden="true"></span>
                        <?php echo Text::_('JSAVE') ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="border-bottom pb-2 mb-3">
            <h1 class="vol_h1">
                <?php echo Text::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS_EDIT') ?>
            </h1>
        </div>

        <?php echo $this->form->renderField('title'); ?>
        <?php echo $this->form->renderField('alias'); ?>

        <hr>

        <?php echo $this->form->renderField('email'); ?>
        <?php echo $this->form->renderField('website'); ?>

        <hr>

        <?php echo $this->form->renderField('description'); ?>

        <hr>

        <div class="filter-bar clearfix">
            <div class="btn-toolbar float-end gap-2">
                <div id="toolbar-cancel" class="btn-group">
                    <button class="btn btn-danger" type="button" data-task="department.cancel">
                        <span class="icon-cancel" aria-hidden="true"></span>
                        <?php echo Text::_('JCANCEL') ?>
                    </button>
                </div>
                <div id="toolbar-apply" class="btn-group">
                    <button class="btn btn-success" type="button" data-task="department.save">
                        <span class="icon-pencil" aria-hidden="true"></span>
                        <?php echo Text::_('JSAVE') ?>
                    </button>
                </div>
            </div>
        </div>

        <input type="hidden" name="option" value="com_volunteers" />
        <input type="hidden" name="task" value="department.save" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
