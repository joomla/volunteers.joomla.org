<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
/** @var \Joomla\Component\Volunteers\Site\View\Faq\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('jquery');
$wa->useScript('jquery-noconflict');
$wa->useScript('jquery-migrate');

// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}


?>

<div class="row">
    <div class="border-bottom pb-2 mb-3">
        <h1 class="vol_h1">
            <?php echo Text::_('COM_VOLUNTEERS_TITLE_FAQ'); ?>
        </h1>
    </div>
    <?php echo Text::_('COM_VOLUNTEERS_FAQ_TEXT'); ?>
</div>

