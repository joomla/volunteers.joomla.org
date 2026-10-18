<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
/** @var \Joomla\Component\Volunteers\Site\View\Registration\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
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
            <?php echo Text::_('COM_VOLUNTEERS_REGISTRATION_HEADING'); ?>
        </h1>
            </div>
    <?php  echo Text::_('COM_VOLUNTEERS_REGISITRATION_BODY'); ?>
        </div>

