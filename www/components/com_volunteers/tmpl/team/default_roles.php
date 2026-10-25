<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Team\HtmlView $this */
?>
<div class="tab-pane">
<?php if ($this->acl->edit) : ?>
    <div class="row">
        <div class="filter-bar">
        <a class="btn btn-light float-end" href="<?php echo Route::_('index.php?option=com_volunteers&task=role.add&team=' . $this->item->id); ?>">
            <span class="icon-new" aria-hidden="true"></span>
            <?php echo Text::_('COM_VOLUNTEERS_ROLE_ADD'); ?>
        </a>
    </div>
    </div>
    <hr>
<?php endif; ?>
<?php if ($this->item->roles) : ?>
    <?php foreach ($this->item->roles as $role) : ?>
        <div class="row">
            <div class="team card card-body mb-3">
                <div class="row">
<div class="col-12">
        <?php if ($this->acl->edit) : ?>
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a class="btn btn-light btn-sm" href="<?php echo Route::_('index.php?option=com_volunteers&task=role.edit&id=' . $role->id); ?>">
                <span class="icon-edit" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_EDIT'); ?>
            </a>
            <a class="btn btn-light btn-sm" href="<?php echo Route::_('index.php?option=com_volunteers&task=role.delete&id=' . $role->id . '&' . Session::getFormToken() . '=1'); ?>">
                <span class="icon-delete" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_DELETE'); ?>
            </a>
        </div>
        <?php endif; ?>
</div>
                </div>
                    <div class="row">

                    <div class="col-8">

                        <h2 class="vol_h2"><?php echo $role->title; ?></h2>
                        <p><?php echo $role->description; ?></p>
                        <?php if ($role->open) : ?>
                            <a class="btn btn-light" onclick="document.getElementById('teamsTab').activateTab(document.getElementById('contact'));">
                                <span class="icon-chevron-right" aria-hidden="true"></span>
                                <?php echo Text::_('COM_VOLUNTEERS_ROLE_APPLY'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-4">
                        <div class="members">
                            <?php if (!empty($role->volunteers)) : ?>
                                <?php foreach ($role->volunteers as $rolevolunteer) : ?>
                                    <a class="tip hasTooltip" title="<?php echo $rolevolunteer->volunteer_name; ?>"
                                        href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $rolevolunteer->volunteer); ?>">
                                        <?php echo VolunteersHelper::image($rolevolunteer->volunteer_image, 'small', false, $rolevolunteer->volunteer_image); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($role->open) : ?>
                                <a class="all-members"  onclick="document.getElementById('teamsTab').activateTab(document.getElementById('getinvolved'));">
                                    <span class="all"><?php echo Text::_('COM_VOLUNTEERS_YOU'); ?></span><span class="number">?</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else : ?>
    <div class="row">
        <p class="alert alert-info"><?php echo Text::_('COM_VOLUNTEERS_NOTE_NO_ROLES'); ?></p>
    </div>
<?php endif;
?>

</div>
