<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

if ($story === null) {
    return;
}
?>
<div class="card card-body mb-3">
    <div class="border-bottom pb-1 mb-2"><strong>
        <?php echo Text::_('COM_VOLUNTEERS_JOOMLASTORY'); ?>
    </strong></div>
    <ul class="list-unstyled volunteer-mods">
        <li class="mb-2">
            <a class="volunteer_link d-flex" href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $story->id); ?>">
                <span class="flex-shrink-0 me-2">
                    <?php echo VolunteersHelper::image($story->image, 'small', false, $story->name); ?>
                </span>
                <div class="flex-grow-1">
                    <h3 class="volunteer-name"><?php echo $story->name; ?></h3>
                    <p class="text-muted volunteer-location">
                        <span class="icon-location" aria-hidden="true"></span>
                        <?php echo VolunteersHelper::location($story->country, $story->city); ?>
                    </p>
                </div>
            </a>
        </li>
        <li>
            <p><?php echo HtmlHelper::_('string.truncate', strip_tags(trim((string) $story->joomlastory)), 500); ?></p>
            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $story->id); ?>#joomlastory" class="btn btn-light">
                <span class="icon-chevron-right" aria-hidden="true"></span>
                <?php echo Text::_('MOD_VOLUNTEERS_STORY_READ_MORE_JOOMLASTORY'); ?>
            </a>
        </li>
    </ul>
</div>
