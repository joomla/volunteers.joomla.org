<?php
/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2016 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('_WF_EXT') or die('RESTRICTED');
?>
<table border="0" cellpadding="4" cellspacing="0" width="100%">
    <tr>
        <td>
            <input type="checkbox" id="dailymotion_autoPlay" />
            <label for="dailymotion_autoPlay" title="<?php echo WFText::_('WF_AGGREGATOR_DAILYMOTION_AUTOPLAY_DESC') ?>" class="tooltip"><?php echo WFText::_('WF_AGGREGATOR_DAILYMOTION_AUTOPLAY') ?></label>
        </td>
    </tr>
    <tr>
        <td><label class="" title="<?php echo WFText::_('WF_LABEL_DIMENSIONS_DESC'); ?>"><?php echo WFText::_('WF_LABEL_DIMENSIONS'); ?></label></td>
        <td colspan="3">
            <select id="dailymotion_width" class="editable">
                <option value="320">320</option>
                <option value="480">480</option>
                <option value="560">560</option>
            </select>
            x
            <select id="dailymotion_height" class="editable">
                <option value="180">180</option>
                <option value="270">270</option>
                <option value="315">315</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <label for="dailymotion_start" title="<?php echo WFText::_('WF_AGGREGATOR_DAILYMOTION_START_DESC') ?>" class="tooltip"><?php echo WFText::_('WF_AGGREGATOR_DAILYMOTION_START') ?></label>
            <input id="dailymotion_start" value="" />
        </td>
    </tr>
</table>