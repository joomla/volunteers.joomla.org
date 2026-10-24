<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Volunteers\Administrator\View\Member\HtmlView;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->addInlineScript(<<<'JS'
document.addEventListener('DOMContentLoaded', () => {
    const team = document.getElementById('jform_team');
    const role = document.getElementById('jform_role');

    if (!team || !role) {
        return;
    }

    // Load the roles of the selected team
    team.addEventListener('change', () => {
        const data = new FormData();
        data.append('team', team.value);
        data.append('role', role.value);

        fetch('index.php?option=com_volunteers&task=roles.getTeamRoles', { method: 'POST', body: data })
            .then((response) => response.text())
            .then((html) => {
                const list = document.createElement('select');
                list.innerHTML = html;

                const fancySelect = role.closest('joomla-field-fancy-select');

                if (fancySelect && fancySelect.choicesInstance) {
                    const choices = [...list.options].map((option) => ({
                        value: option.value,
                        label: option.text,
                        selected: option.selected,
                    }));

                    fancySelect.choicesInstance.clearStore();
                    fancySelect.choicesInstance.setChoices(choices, 'value', 'label', true);
                } else {
                    role.innerHTML = html;
                }
            });
    });
});
JS);
?>
<form action="<?php echo Route::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="member-form" aria-label="<?php echo Text::_('COM_VOLUNTEERS_TITLE_MEMBERS_' . (empty($this->item->id) ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('JDETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderFieldset('item'); ?>
            </div>
            <div class="col-lg-3">
                <fieldset class="form-vertical">
                    <legend class="visually-hidden"><?php echo Text::_('JGLOBAL_FIELDSET_GLOBAL'); ?></legend>
                    <?php echo $this->form->renderField('state'); ?>
                    <?php echo $this->form->renderField('notes'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
        <fieldset id="fieldset-publishingdata" class="options-form">
            <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
            <div>
                <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
            </div>
        </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <?php echo $this->form->renderControlFields(); ?>
</form>
