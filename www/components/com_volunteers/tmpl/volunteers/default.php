<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Volunteers\HtmlView $this */

// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
$active = $this->state->get('filter.active', 1);
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm"
    id="adminForm">

    <div class="volunteers">
        <div class="row">
            <div class="filter-bar d-flex flex-wrap justify-content-end gap-2">
                <div class="input-group w-auto">
                    <label class="visually-hidden" for="filter-search"><?php echo Text::_('COM_VOLUNTEERS_SEARCH_VOLUNTEER'); ?></label>
                    <input type="search" name="filter_search" id="filter-search" class="form-control"
                        value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                        placeholder="<?php echo Text::_('COM_VOLUNTEERS_SEARCH_VOLUNTEER'); ?>">
                    <button class="btn btn-primary" type="submit" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
                        <span class="icon-search" aria-hidden="true"></span>
                    </button>
                    <?php if ($this->state->get('filter.search')) : ?>
                        <button class="btn btn-light" type="button" aria-label="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
                            onclick="this.form.filter_search.value='';this.form.submit();">
                            <span class="icon-times" aria-hidden="true"></span>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="btn-group" role="group" aria-label="<?php echo Text::_('JSTATUS'); ?>">
                    <input type="radio" class="btn-check" name="filter_active" id="filter_active1" value="1"
                        autocomplete="off" onchange="this.form.submit();"<?php echo $active == 1 ? ' checked' : ''; ?>>
                    <label class="btn btn-outline-success" for="filter_active1"><?php echo Text::_('COM_VOLUNTEERS_ACTIVE'); ?></label>
                    <input type="radio" class="btn-check" name="filter_active" id="filter_active2" value="2"
                        autocomplete="off" onchange="this.form.submit();"<?php echo $active == 2 ? ' checked' : ''; ?>>
                    <label class="btn btn-outline-dark" for="filter_active2"><?php echo Text::_('COM_VOLUNTEERS_ALL'); ?></label>
                </div>
            </div>
            <div class="border-bottom pb-2 mb-3">
                <h1 class="vol_h1">
                    <?php echo Text::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS') ?>
                </h1>
            </div>
        </div>

        <div class="row">
            <?php $i = 0; ?>
            <?php if (!empty($this->items)) {
                foreach ($this->items as $item) : ?>
                    <div class="col-2">
                        <div class="card card-body p-2 mb-3">
                            <?php VolunteersHelper::outputHorizontalVolunteer($item); ?>
                        </div>
                    </div>
                    <?php $i++; ?>
                    <?php if ($i == 6) : ?>
                    </div>
                    <div class="row">
                        <?php $i = 0; ?>
                    <?php endif; ?>
                <?php endforeach;
            }
            ?>
        </div>

        <div class="pagination">
            <p class="counter float-end">
                <?php echo $this->pagination->getPagesCounter(); ?>
            </p>

            <?php echo $this->pagination->getPagesLinks(); ?>
        </div>
    </div>
</form>
