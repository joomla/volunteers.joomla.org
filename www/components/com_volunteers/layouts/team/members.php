<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/* @var array $displayData */
?>

<?php if ($displayData->item->active) { ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'teamsTab', 'viewmembers', Text::_('COM_VOLUNTEERS_TAB_MEMBERS')); ?>
    <?php if ($displayData->acl->edit) { ?>
        <div class="clearfix">
            <a class="btn btn-light float-end"
                href="<?php echo Route::_('index.php?option=com_volunteers&task=member.add&team=' . $displayData->item->id); ?>">
                <span class="icon-new" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_MEMBER_ADD'); ?>
            </a>
        </div>
        <hr>
    <?php } ?>
    <?php if ($displayData->item->members->active) : ?>
        <table class="table table-striped table-hover table-vertical-align">
            <thead>
                <th width="30%">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_VOLUNTEER'); ?>
                </th>
                <th width="20%">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION'); ?>
                </th>
                <th>
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_ROLE'); ?>
                </th>
                <th width="12%">
                    <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED'); ?>
                </th>
                <?php if ($displayData->acl->edit) : ?>
                    <th width="10%" class="text-center">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_ADDRESS'); ?>
                    </th>
                    <th width="10%" class="text-center">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_NDA'); ?>
                    </th>
                    <th width="10%"></th>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php foreach ($displayData->item->members->active as $volunteer) : ?>
                    <tr>
                        <td class="volunteer-image">
                            <a class="float-start"
                                href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer); ?>">
                                <?php echo VolunteersHelper::image($volunteer->volunteer_image, 'small', false, $volunteer->volunteer_image); ?>
                            </a>
                            <a
                                href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteer->volunteer); ?>">
                                <?php echo $volunteer->volunteer_name; ?>
                            </a>
                            <span class="text-muted volunteer-location">
                                <span class="icon-location" aria-hidden="true"></span>
                                <?php echo VolunteersHelper::location($volunteer->volunteer_country); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $volunteer->position_title; ?>
                        </td>
                        <td>
                            <?php echo $volunteer->role_title; ?>
                        </td>
                        <td>
                            <?php echo VolunteersHelper::date($volunteer->date_started, 'M Y'); ?>
                        </td>
                        <?php if ($displayData->acl->edit) : ?>
                            <td class="text-center">
                                <?php if ($volunteer->address) { ?>
                                    <span class="icon-checkbox-checked"></span>
                                <?php } else { ?>
                                    <span class="icon-checkbox-unchecked"></span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <?php if ($volunteer->nda) { ?>
                                    <span class="icon-checkbox-checked"></span>
                                <?php } else { ?>
                                    <span class="icon-checkbox-unchecked"></span>
                                <?php } ?>
                            </td>
                            <td>
                                <a class="btn btn-light btn-sm float-end"
                                    href="<?php echo Route::_('index.php?option=com_volunteers&task=member.edit&id=' . $volunteer->id); ?>">
                                    <span class="icon-edit" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_VOLUNTEERS_EDIT'); ?>
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php } ?>
