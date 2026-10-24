<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\Component\Volunteers\Site\View\Registration\HtmlView;

/** @var HtmlView $this */

$this->getDocument()->getWebAssetManager()->useStyle('com_volunteers.frontend');
?>
<div class="row">
    <div class="border-bottom pb-2 mb-3">
        <h1 class="vol_h1">
            <?php echo Text::_('COM_VOLUNTEERS_REGISTRATION_HEADING'); ?>
        </h1>
    </div>
    <?php echo Text::_('COM_VOLUNTEERS_REGISITRATION_BODY'); ?>
</div>
