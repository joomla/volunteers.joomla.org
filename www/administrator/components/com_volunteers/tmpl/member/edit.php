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

$wa = $this->getDocument()->
getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>

<script>
    jQuery(document).ready(function ($) {
        $(".team").change(function () {
            let team = $("#" + this.id).val();
            $.ajax({
                url: 'index.php?option=com_volunteers&task=roles.getTeamRoles',
                type: "POST",
                data: {
                    'team': team,
                    'role': <?php echo $this->item->role; ?>,
                }
            }).done(function (options) {
                $(".roles").html(options);
                $(".roles").trigger("liszt:updated");
            })
        }).trigger('change');
    });
</script>

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
