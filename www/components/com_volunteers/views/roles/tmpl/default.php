<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>

<div class="row-fluid">
    <div class="page-header">
        <h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_ROLESOPEN') ?></h1>
    </div>
    <p class="lead"><?php echo JText::_('COM_VOLUNTEERS_TITLE_ROLESOPEN_INTRO') ?></p>
</div>

<div class="row-fluid">
    <div class="span12">
		<?php foreach ($this->items as $team => $roles): ?>
            <div class="well well">
                <h2 style="margin-top: 0;">
                    <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $roles[0]->team) ?>">
						<?php echo $team; ?>
                    </a>
                </h2>
                <table class="table table-striped table-hover table-vertical-align">
                    <thead>
                    <th><?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE') ?></th>
                    <th width="140px"></th>
                    </thead>
                    <tbody>
					<?php foreach ($roles as $role): ?>
                        <tr>
                            <td>
                                <h3><?php echo $role->title; ?></h3>
								<?php echo $role->description; ?>
                            </td>
                            <td>
                                <a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $role->team . '#getinvolved') ?>">
                                    <span class="icon-edit"></span> <?php echo JText::_('COM_VOLUNTEERS_ROLE_APPLY') ?>
                                </a>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
		<?php endforeach; ?>
    </div>
</div>