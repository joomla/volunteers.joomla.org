<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\View\Member\HtmlView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
/** @var $this HtmlView */

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

<form action="<?php echo Route::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="member-form" class="form-validate">

    <div class="row">
        <div class="col-lg-9">
            <?php echo $this->form->renderFieldset('item'); ?>
        </div>
        <div class="col-lg-3">
            <?php echo $this->form->renderFieldset('details'); ?>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
