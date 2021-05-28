<?php
/**
 * @copyright 	Copyright (c) 2009-2021 Ryan Demmer. All rights reserved
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses
 */

defined('JPATH_PLATFORM') or die;

$this->name = JText::_('WF_PROFILES_FEATURES_LAYOUT');
$this->fieldsname = 'features';
echo JLayoutHelper::render('joomla.content.options_default', $this);
?>

<div class="form-horizontal">
    <?php echo JLayoutHelper::render('edit.layout', $this);?>
    <?php echo JLayoutHelper::render('edit.additional', $this);?>
</div>
<input type="hidden" name="jform[plugins]" value="" />
<input type="hidden" name="jform[rows]" value="" />