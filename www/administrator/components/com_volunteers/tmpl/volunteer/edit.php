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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Volunteers\Administrator\View\Volunteer\HtmlView;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

$noImage = Uri::root() . 'media/com_volunteers/images/joomlaperson.png';
?>
<form action="<?php echo Route::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="volunteer-form" aria-label="<?php echo Text::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS_' . (empty($this->item->id) ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('JDETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php foreach (['account', 'location', 'address'] as $fieldset) : ?>
                    <fieldset id="fieldset-<?php echo $fieldset; ?>" class="options-form">
                        <legend><?php echo Text::_($this->form->getFieldsets()[$fieldset]->label); ?></legend>
                        <div>
                            <?php echo $this->form->renderFieldset($fieldset); ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-3">
                <fieldset class="form-vertical">
                    <legend class="visually-hidden"><?php echo Text::_('JGLOBAL_FIELDSET_GLOBAL'); ?></legend>
                    <?php if ($this->getCurrentUser()->authorise('core.admin')) : ?>
                        <?php echo $this->form->renderField('coc'); ?>
                    <?php endif; ?>
                    <?php echo $this->form->renderField('state'); ?>
                    <?php echo $this->form->renderField('notes'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'profile', Text::_('COM_VOLUNTEERS_FIELDSET_PROFILE')); ?>
        <div class="row">
            <div class="col-lg-9">
                <fieldset id="fieldset-profile" class="options-form">
                    <legend><?php echo Text::_('COM_VOLUNTEERS_FIELDSET_PROFILE'); ?></legend>
                    <div>
                        <?php echo $this->form->renderFieldset('profile'); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-lg-3">
                <fieldset id="fieldset-photo" class="options-form">
                    <legend><?php echo Text::_('COM_VOLUNTEERS_FIELDSET_PHOTO'); ?></legend>
                    <div>
                        <?php // The photo is a link to the image on the Joomla! Identity portal ?>
                        <img class="img-fluid rounded mb-3" src="<?php echo $this->escape($this->item->image ?: $noImage); ?>" alt="">
                        <div class="form-vertical">
                            <?php echo $this->form->renderFieldset('photo'); ?>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'accounts', Text::_('COM_VOLUNTEERS_FIELDSET_ACCOUNTS')); ?>
        <fieldset id="fieldset-accounts" class="options-form">
            <legend><?php echo Text::_('COM_VOLUNTEERS_FIELDSET_ACCOUNTS'); ?></legend>
            <div>
                <?php echo $this->form->renderFieldset('accounts'); ?>
            </div>
        </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <fieldset id="fieldset-publishingdata" class="options-form">
            <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
            <div>
                <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
            </div>
        </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php echo $this->form->renderControlFields(); ?>
</form>
