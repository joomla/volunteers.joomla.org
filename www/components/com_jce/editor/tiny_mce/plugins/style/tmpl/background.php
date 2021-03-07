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
?>
      <div class="uk-form-row uk-grid uk-grid-small">
        <label for="background_color" class="uk-form-label uk-width-2-10"><?php echo JText::_('WF_STYLES_BACKGROUND_COLOR'); ?></label>
            <div class="uk-form-controls uk-width-2-10">
              <input id="background_color" class="color" type="text" value="" />
            </div>
      </div>
      <div class="uk-form-row uk-grid uk-grid-small">
        <label for="background_image" class="uk-form-label uk-width-2-10"><?php echo JText::_('WF_STYLES_BACKGROUND_IMAGE'); ?></label>
          <div class="uk-form-controls uk-width-8-10">
            <input id="background_image" class="browser image" type="text" />
          </div>
      </div>

      <div class="uk-form-row uk-grid uk-grid-small">
        <label for="background_repeat" class="uk-form-label uk-width-2-10"><?php echo JText::_('WF_STYLES_BACKGROUND_REPEAT'); ?></label>
        <div class="uk-form-controls uk-width-4-10">
          <input type="text" id="background_repeat" class="uk-datalist" list="background_repeat_datalist" /><datalist id="background_repeat_datalist"></datalist>
        </div>
      </div>

      <div class="uk-form-row uk-grid uk-grid-small">
        <label for="background_attachment" class="uk-form-label uk-width-2-10"><?php echo JText::_('WF_STYLES_BACKGROUND_ATTACHMENT'); ?></label>
        <div class="uk-form-controls uk-width-4-10">
          <input type="text" id="background_attachment" class="uk-datalist" list="background_attachment_datalist" /><datalist id="background_attachment_datalist"></datalist>
        </div>
      </div>

      <div class="uk-form-row uk-grid uk-grid-small">
        <label for="background_hpos" class="uk-form-label uk-width-2-10"><?php echo JText::_('WF_STYLES_BACKGROUND_HPOS'); ?></label>

          <div class="uk-form-controls uk-width-4-10 uk-margin-right">
              <input type="text" id="background_hpos" class="uk-datalist" list="background_hpos_datalist" /><datalist id="background_hpos_datalist"></datalist>
          </div>
          <div class="uk-form-controls uk-width-2-10">
              <select id="background_hpos_measurement"></select>
          </div>
      </div>

      <div class="uk-form-row uk-grid uk-grid-small">
        <label for="background_vpos" class="uk-form-label uk-width-2-10"><?php echo JText::_('WF_STYLES_BACKGROUND_VPOS'); ?></label>

          <div class="uk-form-controls uk-width-4-10 uk-margin-right">
              <input type="text" id="background_vpos" class="uk-datalist" list="background_vpos_datalist" /><datalist id="background_vpos_datalist"></datalist>
            </div>
          <div class="uk-form-controls uk-width-2-10">
              <select id="background_vpos_measurement"></select>
          </div>
      </div>
