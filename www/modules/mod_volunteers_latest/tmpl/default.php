<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;
$app = \Joomla\CMS\Factory::getApplication();
$model = $app->bootComponent('com_volunteers')
        ->getMVCFactory()
        ->createModel('Volunteers', 'Administrator', ['ignore_request' => true]);

$model->setState('list.limit', (int) $params->get('count', 5));
$model->setState('list.ordering', 'user.registerDate');
$model->setState('list.direction', 'desc');
$model->setState('filter.image', 1);
$volunteers = $model->getItems();
if (empty($volunteers)) {
    return;
}
?>
<div class="card card-body mb-3">
    <div class="border-bottom pb-1 mb-2"><strong>
        <?php echo Text::_('COM_VOLUNTEERS_LATEST_VOLUNTEERS'); ?>
    </strong></div>
    <?php foreach ($volunteers as $item) : ?>
        <ul class="list-unstyled volunteer-mods">
            <li>
                <a class="volunteer_link d-flex" href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id); ?>">
                    <span class="flex-shrink-0 me-2">
                        <?php echo VolunteersHelper::image($item->image, 'small', false, $item->name); ?>
                    </span>
                    <div class="flex-grow-1">
                        <h3 class="volunteer-name"><?php echo $item->name; ?></h3>
                        <p class="text-muted volunteer-location">
                            <span class="icon-location" aria-hidden="true"></span>
                            <?php echo VolunteersHelper::location($item->country, $item->city); ?>
                        </p>
                    </div>
                </a>
            </li>
        </ul>
    <?php endforeach; ?>
    <a class="btn btn-light btn-lg w-100" href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteers'); ?>">
        <?php echo Text::_('MOD_VOLUNTEERS_READ_MORE_VOLUNTEERS'); ?>
    </a>
</div>
