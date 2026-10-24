<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Roles\HtmlView $this */

// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
    // Filter the roles on the page while typing in the search box
    $wa->addInlineScript(<<<'JS'
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('filter-search');
    const clear  = document.getElementById('filter-clear');
    const empty  = document.getElementById('roles-empty');
    const teams  = document.querySelectorAll('.roles-team');

    const filter = () => {
        const terms = search.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
        let found   = 0;

        teams.forEach((team) => {
            const name  = team.querySelector('.vol_h2').textContent.toLowerCase();
            let visible = 0;

            team.querySelectorAll('tbody tr').forEach((row) => {
                const text  = name + ' ' + row.textContent.toLowerCase();
                const match = terms.every((term) => text.includes(term));
                row.hidden  = !match;
                visible += match ? 1 : 0;
            });

            team.hidden = !visible;
            found += visible;
        });

        clear.hidden = !terms.length;
        empty.hidden = !!found;
    };

    search.addEventListener('input', filter);
    clear.addEventListener('click', () => {
        search.value = '';
        filter();
        search.focus();
    });
});
JS);
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
?>

<div class="row">
    <div class="filter-bar d-flex flex-wrap justify-content-end gap-2">
        <div class="input-group w-auto">
            <label class="visually-hidden" for="filter-search"><?php echo Text::_('COM_VOLUNTEERS_SEARCH_ROLE'); ?></label>
            <input type="text" id="filter-search" class="form-control" autocomplete="off"
                placeholder="<?php echo Text::_('COM_VOLUNTEERS_SEARCH_ROLE'); ?>">
            <span class="input-group-text" aria-hidden="true">
                <span class="icon-search"></span>
            </span>
            <button class="btn btn-light" type="button" id="filter-clear" hidden aria-label="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
                <span class="icon-times" aria-hidden="true"></span>
            </button>
        </div>
    </div>
    <div class="border-bottom pb-2 mb-3">
        <h1 class="vol_h1">
            <?php echo Text::_('COM_VOLUNTEERS_TITLE_ROLESOPEN') ?>
        </h1>
    </div>
    <p class="lead">
        <?php echo Text::_('COM_VOLUNTEERS_TITLE_ROLESOPEN_INTRO') ?>
    </p>
</div>

<div class="row">
    <div class="col-12">
        <?php foreach ($this->items as $team => $roles) : ?>
            <div class="roles-team card card-body mb-3">
                <h2 class="vol_h2" style="margin-top: 0;">
                    <a href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $roles[0]->team) ?>"
                        id="<?php echo OutputFilter::stringURLSafe($team); ?>">
                        <?php echo $team; ?>
                    </a>
                </h2>
                <table class="table table-striped table-hover table-vertical-align">
                    <thead>
                        <th>
                            <?php echo Text::_('COM_VOLUNTEERS_FIELD_ROLE') ?>
                        </th>
                        <th width="300px"></th>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role) : ?>
                            <tr>
                                <td>
                                    <h3 class="vol_h3">
                                        <?php echo $role->title; ?>
                                    </h3>
                                    <?php echo $role->description; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <a class="btn btn-light btn-sm"
                                            href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $role->team) . '#getinvolved' ?>">
                                            <span class="icon-chevron-right" aria-hidden="true"></span>
                                            <?php echo Text::_('COM_VOLUNTEERS_ROLE_APPLY') ?>
                                        </a>
                                        <a class="btn btn-light btn-sm"
                                            href="<?php echo Route::_('index.php?option=com_volunteers&view=team&id=' . $role->team) . '#contact' ?>">
                                            <span class="icon-mail" aria-hidden="true"></span>
                                            <?php echo Text::_('COM_VOLUNTEERS_TAB_CONTACT') ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        <p class="alert alert-info" id="roles-empty" hidden>
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </p>
    </div>
</div>
