<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>

<ul class="media-list">
    <li class="media">
        <a class="pull-left" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $story->id) ?>">
			<?php echo VolunteersHelper::image($story->image, 'small', false, $story->name); ?>
        </a>
        <div class="media-body">
            <h3 class="media-heading">
                <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $story->id) ?>">
					<?php echo $story->name; ?>
                </a>
            </h3>
            <p class="muted">
                <span class="icon-location"></span> <?php echo VolunteersHelper::location($story->country, $story->city); ?>
            </p>
        </div>
    </li>
    <li class="media">
        <p><?php echo JHtml::_('string.truncate', strip_tags(trim($story->joomlastory)), 500); ?></p>
        <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $story->id) ?>#joomlastory" class="btn">
            <span class="icon-chevron-right"></span><?php echo JText::_('COM_VOLUNTEERS_READ_MORE_JOOMLASTORY') ?>
        </a>
    </li>
</ul>
