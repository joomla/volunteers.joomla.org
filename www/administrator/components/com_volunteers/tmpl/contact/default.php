<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
/** @var \Joomla\Component\Volunteers\Administrator\View\Contact\HtmlView $this */
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$this->getDocument()->getWebAssetManager()
    ->useScript('keepalive')
    ->useScript('form.validate');
?>

<form action="<?php echo Route::_('index.php?option=com_volunteers&view=contact'); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row">
        <div class="col-lg-7">
            <fieldset class="options-form">
                <legend><?php echo Text::_('COM_VOLUNTEERS_CONTACT_MESSAGE'); ?></legend>
                <?php echo $this->form->renderFieldset('message'); ?>
            </fieldset>
        </div>
        <div class="col-lg-5">
            <fieldset class="options-form">
                <legend><?php echo Text::plural('COM_VOLUNTEERS_CONTACT_N_RECIPIENTS', count($this->recipients)); ?></legend>
                <?php if ($this->recipients) : ?>
                    <table class="table">
                        <caption class="visually-hidden"><?php echo Text::_('COM_VOLUNTEERS_CONTACT_RECIPIENTS'); ?></caption>
                        <thead>
                            <tr>
                                <th scope="col"><?php echo Text::_('COM_VOLUNTEERS_FIELD_VOLUNTEER'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->recipients as $recipient) : ?>
                                <tr>
                                    <th scope="row">
                                        <?php echo $this->escape($recipient['name']); ?>
                                        <div class="small fw-normal"><?php echo $this->escape($recipient['email']); ?></div>
                                    </th>
                                    <td>
                                        <?php echo $this->escape($recipient['position']); ?>
                                        <div class="small"><?php echo $this->escape($recipient['team']); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_VOLUNTEERS_CONTACT_NO_RECIPIENTS'); ?>
                    </div>
                <?php endif; ?>
            </fieldset>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
