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

/** @var \Joomla\Component\Volunteers\Site\View\Volunteer\HtmlView $this */
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

<div class="volunteer-edit">

    <form id="volunteer"
        action="<?php echo Route::_('index.php?option=com_volunteers&task=volunteer.save&id=' . (int) $this->item->id); ?>"
        method="post" class="form-validate" enctype="multipart/form-data">
        <div class="row">
            <div class="filter-bar">
                <div class="btn-toolbar float-end">
                    <div id="toolbar-cancel" class="btn-group">
                        <button class="btn btn-danger"  type="button" onclick="history.back();return false;">
                            <span class="icon-cancel" aria-hidden="true"></span>
                            <?php echo Text::_('JCANCEL') ?>
                        </button>
                    </div>
                    <div id="toolbar-apply" class="btn-group">
                        <button class="btn btn-success" type="submit">
                            <span class="icon-pencil" aria-hidden="true"></span>
                            <?php echo Text::_('JSAVE') ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="border-bottom pb-2 mb-3">
                <h1 class="vol_h1">
                    <?php echo Text::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS_EDIT_MY') ?>
                </h1>
            </div>
        </div>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_ACCOUNT') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_NAME_DESC') ?>
                </div>
            </div>
        </div>
        <?php echo $this->form->renderField('name'); ?>
        <?php echo $this->form->renderField('email'); ?>
        <?php echo $this->form->renderField('password1'); ?>
        <?php echo $this->form->renderField('password2'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_BIRTHDAY') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_BIRTHDAY_DESC') ?>
                </div>
            </div>
        </div>

        <?php echo $this->form->renderField('birthday'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_PHOTO') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_IMAGE_DESC') ?>
                </div>
            </div>
        </div>

        <?php echo $this->form->renderField('image'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_LOCATION') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_PROFILE_LOCATION_DESC') ?>
                </div>
            </div>
        </div>

        <?php echo $this->form->renderField('country'); ?>
        <?php echo $this->form->renderField('city-location'); ?>
        <?php echo $this->form->renderField('location'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_ADDRESS') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_PROFILE_ADDRESS_DESC') ?>
                </div>
            </div>
        </div>

        <?php if ($this->item->teams->activemember) : ?>
            <div class="control-group">
                <div class="controls">
                    <div class="alert alert-warning">
                        <?php echo Text::_('COM_VOLUNTEERS_PROFILE_ACTIVEMEMBER_DESC') ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="control-group">
            <div class="control-label">
                <?php echo $this->form->getLabel('address'); ?>
            </div>
            <div class="controls">
                <?php echo $this->form->getInput('address'); ?>
                <br><small><em>Street address / P.O. Box</em></small>
            </div>
        </div>

        <?php echo $this->form->renderField('city'); ?>
        <?php echo $this->form->renderField('region'); ?>
        <?php echo $this->form->renderField('zip'); ?>

        <div class="control-group checkbox">
            <div class="controls">
                <?php echo $this->form->getInput('send_permission'); ?>
                <?php echo $this->form->getLabel('send_permission'); ?>
            </div>
        </div>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_JOOMLA') ?>
        </h3>

        <?php echo $this->form->renderField('joomlaforum'); ?>
        <?php echo $this->form->renderField('joomladocs'); ?>
        <?php echo $this->form->renderField('certification'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_SOCIAL') ?>
        </h3>

        <?php echo $this->form->renderField('website'); ?>
        <?php echo $this->form->renderField('github'); ?>
        <?php echo $this->form->renderField('crowdin'); ?>
        <?php echo $this->form->renderField('stackexchange'); ?>
        <?php echo $this->form->renderField('joomlastackexchange'); ?>
        <?php echo $this->form->renderField('twitter'); ?>
        <?php echo $this->form->renderField('facebook'); ?>
        <?php echo $this->form->renderField('linkedin'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_INTRODUCTION') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_INTRO_DESC') ?>
                </div>
            </div>
        </div>

        <?php echo $this->form->renderField('intro'); ?>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_JOOMLASTORY_DESC') ?>
                </div>
            </div>
        </div>

        <?php echo $this->form->renderField('joomlastory'); ?>

        <hr>

        <h3 class="vol_h3">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_SETTINGS') ?>
        </h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_PEAKON_INTRO'); ?>
                </div>
            </div>
        </div>

        <?php echo $this->form->renderField('peakon'); ?>

        <hr>

        <div class="filter-bar">
            <div class="btn-toolbar float-end">
                <div id="toolbar-cancel" class="btn-group">
                        <button class="btn btn-danger"  type="button" onclick="history.back();return false;">
                        <span class="icon-cancel" aria-hidden="true"></span>
                        <?php echo Text::_('JCANCEL') ?>
                        </button>
                </div>
                <div id="toolbar-apply" class="btn-group">
                    <button class="btn btn-success" type="submit">
                        <span class="icon-pencil" aria-hidden="true"></span>
                        <?php echo Text::_('JSAVE') ?>
                    </button>
                </div>

            </div>
        </div>

        <input type="hidden" name="option" value="com_volunteers" />
        <input type="hidden" name="task" value="volunteer.save" />
        <?php echo HtmlHelper::_('form.token'); ?>
    </form>
</div>
