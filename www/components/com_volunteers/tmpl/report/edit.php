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

/** @var \Joomla\Component\Volunteers\Site\View\Report\HtmlView $this */
// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
$wa->useScript('keepalive');
$wa->useScript('form.validate');
//HTMLHelper::_('formbehavior.chosen', 'select');;
?>

<div class="report-edit">

    <form id="report"
          action="<?php
            echo Route::_('index.php?option=com_volunteers&task=report.save&id=' . (int)$this->item->id); ?>"
          method="post" class="form-validate" enctype="multipart/form-data">
        <div class="row">
            <div class="filter-bar">
                <div class="btn-bottom-toolbar float-end">
                    <div id="toolbar-cancel" class="btn-group">
                        <button class="btn btn-danger" type="button" onclick="history.back();return false;">
                            <span class="icon-cancel" aria-hidden="true"></span>
                            <?php
                            echo Text::_('JCANCEL') ?>
                        </button>
                    </div>
                    <div id="toolbar-apply" class="btn-group">
                        <button class="btn btn-success" type="submit">
                            <span class="icon-pencil" aria-hidden="true"></span>
                            <?php
                            echo Text::_('JSAVE') ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="border-bottom pb-2 mb-3">
                <h1 class="vol_h1">
                    <?php
                    echo Text::_('COM_VOLUNTEERS_TITLE_REPORTS_EDIT') ?>
                </h1>
            </div>
        </div>

        <?php
        if ($this->item->department) : ?>
            <?php
            echo $this->form->renderField('department'); ?>
            <?php
        endif; ?>
        <?php
        if ($this->item->team) : ?>
            <?php
            echo $this->form->renderField('team'); ?>
            <?php
        endif; ?>

        <hr>

        <?php
        echo $this->form->renderField('title'); ?>
        <?php
        echo $this->form->renderField('alias'); ?>

        <hr>

        <?php
        echo $this->form->renderField('created'); ?>

        <hr>

        <?php
        echo $this->form->renderField('description'); ?>

        <hr>
        <p><br/><br/><br/></p>
        <div class="filter-bar">
            <div class="btn-bottom-toolbar float-end">
                <div id="toolbar-cancel" class="btn-group">
                    <button class="btn btn-danger" type="button" onclick="history.back();return false;">
                        <span class="icon-cancel" aria-hidden="true"></span>
                        <?php
                        echo Text::_('JCANCEL') ?>
                    </button>
                </div>
                <div id="toolbar-apply" class="btn-group">
                    <button class="btn btn-success" type="submit">
                        <span class="icon-pencil" aria-hidden="true"></span>
                        <?php
                        echo Text::_('JSAVE') ?>
                    </button>
                </div>
            </div>
        </div>

        <input type="hidden" name="option" value="com_volunteers"/>
        <input type="hidden" name="task" value="report.save"/>
        <?php
        echo HtmlHelper::_('form.token'); ?>
    </form>
</div>
