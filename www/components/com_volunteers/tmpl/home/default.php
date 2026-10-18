<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

/** @var \Joomla\Component\Volunteers\Site\View\Home\HtmlView $this */

// phpcs:enable PSR1.Files.SideEffects
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

// Import CSS
try {
    $js_config = [
        'markers'   => json_encode($this->markers)
    ];
    Factory::getApplication()->getDocument()->addScriptOptions('com_volunteers_maps', $js_config);
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend')
        ->useStyle('com_volunteers.leaflet-css')
        ->useStyle('com_volunteers.marker-cluster-css')
        ->useStyle('com_volunteers.marker-cluster-default-css')
        ->useScript('com_volunteers.leaflet-js')
        ->useScript('com_volunteers.marker-cluster-js')
        ->useScript('com_volunteers.home-page-map-js');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}


?>

<img class="img-fluid w-100 mb-4" src="media/com_volunteers/images/volunteer-header.png" alt="Become a Joomla! contributor">
<div class="row">
    <div class="col-6">
        <h2 class="vol_h2">
            <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_HOW_TITLE'); ?>
        </h2>
        <p>
            <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_HOW_DESC'); ?>
        </p>
        <p>
            <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_HOW_ACTION'); ?>
        </p>
        <p>
            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=roles'); ?>" class="btn btn-light"><span class="icon-chevron-right" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_HOW_BUTTON'); ?>
            </a>
        </p>
    </div>
    <div class="col-6">
        <h2 class="vol_h2">
            <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_WHY_TITLE'); ?>
        </h2>
        <p>
            <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_WHY_DESC'); ?>
        </p>
        <p>
            <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_WHY_ACTION'); ?>
        </p>
        <p>
            <a href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteers'); ?>" class="btn btn-light"><span class="icon-chevron-right" aria-hidden="true"></span>
                <?php echo Text::_('COM_VOLUNTEERS_HOME_INTRO_WHY_BUTTON'); ?>
            </a>
        </p>
    </div>
</div>
<hr>
<div class="latest-reports">
    <h2 class="vol_h2">
        <?php echo Text::_('COM_VOLUNTEERS_LATEST_REPORTS') ?>
    </h2>
    <?php if (!empty($this->reports)) {
        foreach ($this->reports as $i => $item) : ?>
            <div class="row report">
                <div class="col-2">

                    <a
                        href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->volunteer_id) ?>">
                        <?php echo VolunteersHelper::image($item->volunteer_image, 'large', false, $item->volunteer_name); ?>
                    </a>
                </div>
                <div class="col-10">
                    <h3 class="vol_h3">
                        <a href="<?php echo Route::_('index.php?option=com_volunteers&view=report&id=' . $item->id) ?>">
                            <?php echo ($item->title); ?>
                        </a>
                    </h3>
                    <p class="text-muted">
                        <?php echo Text::_('COM_VOLUNTEERS_BY') ?>
                        <a
                            href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->volunteer_id) ?>"><?php echo $item->volunteer_name; ?></a>
                        <?php echo Text::_('COM_VOLUNTEERS_ON') ?>
                        <?php echo VolunteersHelper::date($item->created, 'Y-m-d H:i'); ?>
                        <?php echo Text::_('COM_VOLUNTEERS_IN') ?>
                        <a href="<?php echo $item->link; ?>"><?php echo $item->name; ?></a>
                    </p>
                    <p>
                        <?php echo StringHelper::truncate(strip_tags(trim((string) $item->description)), 380); ?>
                    </p>
                    <a href="<?php echo Route::_('index.php?option=com_volunteers&view=report&id=' . $item->id) ?>" class="btn btn-light">
                        <span class="icon-chevron-right" aria-hidden="true"></span>
                        <?php echo Text::_('COM_VOLUNTEERS_READ_MORE') ?>&nbsp;
                        <?php echo HtmlHelper::_('string.truncate', $item->title, 55); ?>
                    </a>
                </div>
            </div>
            <hr class="my-4">
        <?php endforeach;
    }
    ?>
    <a class="btn btn-light btn-lg w-100"
        href="<?php echo Route::_('index.php?option=com_volunteers&view=reports'); ?>"><?php echo Text::_('COM_VOLUNTEERS_READ_MORE_REPORTS') ?></a>
</div>
<hr>
<div class="volunteers-map">
    <h2 class="vol_h2">
        <?php echo count($this->markers) . ' ' . Text::_('COM_VOLUNTEERS_VOLUNTEERS_WORLD') ?>
    </h2>
    <div id="map-canvas"></div>
</div>

