<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
/** @var \Joomla\Component\Volunteers\Administrator\View\Volunteer\HtmlView $this */
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('jquery');
$wa->useScript('jquery-noconflict');
$wa->useScript('jquery-migrate');

$wa->useScript('form.validate');
//HTMLHelper::_('formbehavior.chosen', 'select');;

/*$this->getDocument()->
getWebAssetManager()->addScriptDeclaration("
Joomla.submitbutton = function(task)
{
if (task == 'volunteer.cancel' || document.formvalidator.isValid(document.getElementById('volunteer-form'))) {
" . $this->form->getField('joomlastory')->save() . "
Joomla.submitform(task, document.getElementById('volunteer-form'));
}
};
");*/
?>

<form action="<?php echo Route::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" name="adminForm" id="volunteer-form" class="form-validate">
    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('JDETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <div>
                    <fieldset class="adminform">
                        <?php echo $this->form->renderFieldset('item'); ?>
                    </fieldset>
                </div>
            </div>
            <div class="col-lg-3">
                <?php if (Factory::getApplication()->getIdentity()->authorise('core.admin')) : ?>
                    <h3>
                        <?php echo Text::_('COM_VOLUNTEERS_SECRETARY_ONLY') ?>
                    </h3>
                    <div class="control-group checkbox">
                        <div class="controls">
                            <?php echo $this->form->getInput('coc'); ?>
                            <?php echo $this->form->getLabel('coc'); ?>
                        </div>
                    </div>

                <?php endif; ?>
                <?php echo $this->form->renderFieldset('details'); ?>
            </div>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
    jQuery(document).ready(function () {
        jQuery('.location').on('change', function (e) {
            let city = jQuery('.location-city').val();
            let country = jQuery('.location-country').val();
            jQuery('.gllpSearchField').val(city + ', ' + country);
            jQuery('.gllpSearchButton').click();
        });
    });
</script>
