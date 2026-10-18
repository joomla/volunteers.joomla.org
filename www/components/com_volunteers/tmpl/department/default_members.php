<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Department\HtmlView $this */
?>
<?php if ($this->acl->edit) : ?>
    <div class="row">
        <div class="filter-bar">
        <a class="btn btn-light float-end" href="<?php echo Route::_('index.php?option=com_volunteers&task=member.add&department=' . $this->item->id) ?>">

            <span class="icon-new" aria-hidden="true"></span>
            <?php echo Text::_('COM_VOLUNTEERS_MEMBER_ADD') ?>
        </a>
         </div>
    </div>
    <hr>
<?php endif; ?>
<?php if ($this->item->members->active) : ?>
    <table class="table table-striped table-hover table-vertical-align">
        <thead>
        <th width="30%">
            <?php echo Text::_('COM_VOLUNTEERS_FIELD_VOLUNTEER') ?>
        </th>
        <th>
            <?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION') ?>
        </th>
        <th width="12%">
            <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?>
        </th>
        <?php if ($this->acl->edit) : ?>
            <th width="10%">
                <?php echo Text::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?>
            </th>
        <?php endif; ?>
        </thead>
        <tbody>
        <?php foreach ($this->item->members->active as $volunteer) : ?>
            <tr>
                <td class="volunteer-image">


                    <?php VolunteersHelper::OutputVolunteer($volunteer); ?>

                </td>
                <td>
                    <?php echo $volunteer->position_title; ?>
                </td>
                <td>
                    <?php echo VolunteersHelper::date($volunteer->date_started, 'M Y'); ?>
                </td>
                <?php if ($this->acl->edit) : ?>
                    <td class="td-editbtn">
                        <a class="btn btn-light"
                           href="<?php echo Route::_('index.php?option=com_volunteers&task=member.edit&id=' . $volunteer->id) ?>">
                        <span class="icon-edit" aria-hidden="true"></span>
                            <?php echo Text::_('COM_VOLUNTEERS_EDIT') ?>
                        </a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;
