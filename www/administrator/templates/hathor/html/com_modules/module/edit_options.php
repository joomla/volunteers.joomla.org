<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Template.hathor
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

	$fieldSets = $this->form->getFieldsets('params');

	foreach ($fieldSets as $name => $fieldSet) :
		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MODULES_'.$name.'_FIELDSET_LABEL';
		echo JHtml::_('sliders.panel', JText::_($label), $name.'-options');
			if (isset($fieldSet->description) && trim($fieldSet->description)) :
				echo '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
			endif;
			?>
		<fieldset class="panelform">
		<legend class="element-invisible"><?php echo JText::_($label); ?></legend>
		<?php $hidden_fields = ''; ?>
		<ul class="adminformlist">
			<?php foreach ($this->form->getFieldset($name) as $field) : ?>
			<?php if (!$field->hidden) : ?>
			<li>
				<?php echo $field->label; ?>
				<?php echo $field->input; ?>
			</li>
			<?php else : $hidden_fields .= $field->input; ?>
			<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php echo $hidden_fields; ?>
		</fieldset>
	<?php endforeach; ?>
