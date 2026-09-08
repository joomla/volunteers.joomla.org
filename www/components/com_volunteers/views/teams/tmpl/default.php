<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$active = $this->state->get('filter.active', 1);
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">

    <div class="row-fluid">
        <div class="filter-bar">
            <div class="btn-group float-end">
                <label class="filter-search-lbl sr-only" for="filter-search">
					<?php echo JText::_('COM_VOLUNTEERS_SEARCH_TEAM') . '&#160;'; ?>
                </label>
                <div class="input-group">
                    <input class="form-control" type="text" name="filter_search" id="filter-search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" class="inputbox" onchange="document.adminForm.submit();" placeholder="<?php echo JText::_('COM_VOLUNTEERS_SEARCH_TEAM'); ?>"/>
                    <button class="btn btn-primary" type="submit" value="<?php echo JText::_('COM_VOLUNTEERS_SEARCH_TEAM'); ?>">
                        <span class="icon-search"></span></button>
					<?php if ($this->state->get('filter.search')): ?>
                        <button class="btn btn-secondary" type="reset" onclick="jQuery('#filter-search').attr('value', null);document.adminForm.submit();">
                            <span class="icon-remove"></span>
                        </button>
					<?php endif; ?>
                </div>
            </div>

            <fieldset id="filter_active" class="btn-group radio float-end" onchange="document.adminForm.submit();">
                <input class="btn-check" type="radio" id="filter_active1" name="filter_active" value="1" <?php if ($active == 1): ?>selected="selected"<?php endif; ?>>
                <label for="filter_active1" class="btn<?php if ($active == 1): ?> btn-success<?php endif; ?>"><?php echo JText::_('COM_VOLUNTEERS_ACTIVE') ?></label>

                <input class="btn-check" type="radio" id="filter_active0" name="filter_active" value="0" <?php if ($active == 0): ?>selected="selected"<?php endif; ?>>
                <label for="filter_active0" class="btn<?php if ($active == 0): ?> btn-danger<?php endif; ?>"><?php echo JText::_('COM_VOLUNTEERS_ARCHIVED') ?></label>

                <input class="btn-check" type="radio" id="filter_active2" name="filter_active" value="2" <?php if ($active == 2): ?>selected="selected"<?php endif; ?>>
                <label for="filter_active2" class="btn<?php if ($active == 2): ?> btn-inverse<?php endif; ?>"><?php echo JText::_('COM_VOLUNTEERS_ALL') ?></label>
            </fieldset>
        </div>
        <div class="page-header">
			<?php if ($this->state->get('filter.groups')): ?>
                <h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_GROUPS') ?></h1>
			<?php else: ?>
                <h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_TEAMS') ?></h1>
			<?php endif; ?>
        </div>
    </div>
	<?php if (!empty($this->items)) foreach ($this->items as $i => $item): ?>
        <div class="card bg-dark-subtle mb-3">
            <div class="team card-body team-<?php echo($item->id); ?>">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mt-0 h4">
                            <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>">
								<?php echo($item->title); ?><?php if ($item->acronym): ?> (<?php echo($item->acronym) ?>)<?php endif; ?>
                            </a>
							<?php if ($item->date_ended != '0000-00-00'): ?>
                                <small><?php echo JText::_('COM_VOLUNTEERS_ARCHIVED') ?></small>
							<?php endif; ?>
                            <span class="badge bg-info badge-sm">
                                <?php if ($item->status == '0'): ?>
	                                <?php echo JText::_('COM_VOLUNTEERS_FIELD_STATUS_INFORMATION') ?>
                                <?php elseif ($item->status == '1'): ?>
	                                <?php echo JText::_('COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL') ?>
                                <?php elseif ($item->status == '2'): ?>
	                                <?php echo JText::_('COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL') ?>
                                <?php endif; ?>
                            </span>
                        </h2>
                        <p><?php echo($item->description); ?></p>

						<?php if (count($item->subteams)): ?>
                            <h3><?php echo JText::_('COM_VOLUNTEERS_SUBTEAMS') ?></h3>
                            <ul class="nav nav-tabs nav-stacked">
								<?php foreach ($item->subteams as $subteam): ?>
                                    <li>
                                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $subteam->id) ?>">
											<?php echo($subteam->title); ?><?php if ($subteam->acronym): ?> (<?php echo($subteam->acronym) ?>)<?php endif; ?>
                                            <span class="label label-info">
                                                <?php if ($subteam->status == '0'): ?>
	                                                <?php echo JText::_('COM_VOLUNTEERS_FIELD_STATUS_INFORMATION') ?>
                                                <?php elseif ($subteam->status == '1'): ?>
	                                                <?php echo JText::_('COM_VOLUNTEERS_FIELD_STATUS_OFFICIAL') ?>
                                                <?php elseif ($subteam->status == '2'): ?>
	                                                <?php echo JText::_('COM_VOLUNTEERS_FIELD_STATUS_UNOFFICIAL') ?>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    </li>
								<?php endforeach; ?>
                            </ul>
						<?php endif; ?>
                        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>" class="btn btn-secondary">
                            <span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_READ_MORE') . ' ' . $item->title; ?>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <div class="members">
							<?php $i = 0; ?>
							<?php if (!empty($item->members)) foreach ($item->members as $member): ?>
                                <a class="tip hasTooltip" title="<?php echo $member->volunteer_name; ?>" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $member->volunteer) ?>">
									<?php echo VolunteersHelper::image($member->volunteer_image, 'small', false, $member->volunteer_name); ?>
                                </a>
								<?php $i++;
								if ($i == 14)
								{
									break;
								}; ?>
							<?php endforeach; ?>
							<?php if (count($item->members) > 14): ?>
                                <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $item->id) ?>" class="all-members">
                                    <span class="all"><?php echo JText::_('COM_VOLUNTEERS_ALL') ?></span><span class="number"><?php echo(' ' . count($item->members)); ?></span>
                                </a>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	<?php endforeach; ?>

    <div class="pagination">
        <p class="counter float-end">
			<?php echo $this->pagination->getPagesCounter(); ?>
        </p>

		<?php echo $this->pagination->getPagesLinks(); ?>
    </div>
</form>