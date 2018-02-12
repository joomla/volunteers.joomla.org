<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>

<?php if (!empty($volunteers)) foreach ($volunteers as $i => $item): ?>
    <ul class="media-list latest-volunteers">
        <li class="media">
            <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id) ?>">
						<span class="pull-left">
							<?php echo VolunteersHelper::image($item->image, 'small', false, $item->name); ?>
						</span>
                <div class="media-body">
                    <h3 class="media-heading">
						<?php echo $item->name; ?>
                    </h3>
                    <p class="muted">
                        <span class="icon-location"></span> <?php echo VolunteersHelper::location($item->country, $item->city); ?>
                    </p>
                </div>
            </a>
        </li>
    </ul>
<?php endforeach; ?>
<a class="btn btn-large btn-block" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteers'); ?>"><?php echo JText::_('COM_VOLUNTEERS_READ_MORE_VOLUNTEERS') ?></a>