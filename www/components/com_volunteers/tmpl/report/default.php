<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var HtmlView $this */
?>

<div class="row report">
    <div class="col-2 volunteer-image">
        <a
            href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->item->volunteer_id) ?>">
            <?php echo VolunteersHelper::image($this->item->volunteer_image, 'large', false, $this->item->volunteer_name); ?>
        </a>
    </div>
    <div class="col-10">
        <div class="filter-bar">
            <?php if ($this->acl->edit || ($this->item->created_by == $this->user->id)) : ?>
                <a class="btn btn-light float-end"
                    href="<?php echo Route::_('index.php?option=com_volunteers&task=report.edit&id=' . $this->item->id) ?>">
                    <span class="icon-edit" aria-hidden="true"></span>
                    <?php echo Text::_('COM_VOLUNTEERS_EDIT') ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="border-bottom pb-2 mb-3">
            <h1 class="vol_h1">
                <?php echo $this->item->title ?>
            </h1>
        </div>

        <p class="text-muted">
            <?php echo Text::_('COM_VOLUNTEERS_BY') ?>
            <a
                href="<?php echo Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->item->volunteer_id) ?>"><?php echo $this->item->volunteer_name; ?></a>
            <?php echo Text::_('COM_VOLUNTEERS_ON') ?>
            <?php echo VolunteersHelper::date($this->item->created, 'Y-m-d H:i'); ?>
            <?php echo Text::_('COM_VOLUNTEERS_IN') ?>
            <a href="<?php echo $this->item->link; ?>"><?php echo $this->item->name; ?></a>
        </p>

        <?php echo ($this->item->description) ?>

    </div>
</div>

<div class="share">
    <?php echo $this->share; ?>
</div>
