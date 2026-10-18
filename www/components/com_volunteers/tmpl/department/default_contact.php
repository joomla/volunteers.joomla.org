<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Department\HtmlView $this */
?>
<div class="tab-pane" id="contact">
    <?php if ($this->user->guest) : ?>
        <p class="alert alert-info">
            <?php echo Text::_('COM_VOLUNTEERS_NOTE_LOGIN_CONTACT_DEPARTMENT') ?>
        </p>
    <?php else : ?>
        <form class="form" name="sendmail" action="<?php echo Route::_('index.php') ?>"
              method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label" for="to_name"><?php echo Text::_('COM_VOLUNTEERS_MESSAGE_TO') ?></label>
                <div>
                    <input type="text" name="to_name" id="to_name" value="<?php echo $this->item->title ?>"
                           class="form-control" disabled="disabled" />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="from_name"><?php echo Text::_('COM_VOLUNTEERS_MESSAGE_FROM') ?></label>
                <div>
                    <input type="text" name="from_name" id="from_name"
                           value="<?php echo ($this->user->name); ?> <<?php echo ($this->user->email); ?>>"
                           class="form-control" disabled="disabled" />
                </div>
            </div>
            <div class="mb-3">
                <div>
                    <input type="text" name="subject" id="subject" class="form-control"
                           placeholder="<?php echo Text::_('COM_VOLUNTEERS_MESSAGE_SUBJECT') ?>" required />
                </div>
            </div>
            <div class="mb-3">
                        <textarea rows="10" name="message" id="message" class="form-control"
                                  placeholder="<?php echo Text::sprintf('COM_VOLUNTEERS_MESSAGE_BODY', $this->item->title) ?>"
                                  required></textarea>
            </div>
            <div class="alert alert-info">
                <?php echo Text::sprintf('COM_VOLUNTEERS_MESSAGE_NOTICE', $this->item->title) ?>
            </div>
            <div class="mb-3">
                <input type="submit" value="<?php echo Text::_('COM_VOLUNTEERS_MESSAGE_SUBMIT') ?>" name="submit"
                       id="submitButton" class="btn btn-success float-end" />
            </div>

            <input type="hidden" name="option" value="com_volunteers" />
            <input type="hidden" name="task" value="department.sendmail" />
            <?php echo HtmlHelper::_('form.token'); ?>
        </form>
    <?php endif; ?>
</div>
