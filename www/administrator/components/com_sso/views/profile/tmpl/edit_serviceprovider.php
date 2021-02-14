<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// phpcs:disable
?>
<ul class="nav nav-tabs" id="configTabs">
	<?php foreach ($this->providerForm->getFieldsets() as $name => $fieldSet) : ?>
		<?php $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
		<li><a data-toggle="tab" href="#<?php echo $name; ?>"><?php echo Text::_($label); ?></a></li>
	<?php endforeach; ?>
</ul>

<div class="tab-content" id="configContent">
	<?php foreach ($this->providerForm->getFieldsets() as $name => $fieldSet) : ?>
		<div class="tab-pane" id="<?php echo $name; ?>">
			<?php if (isset($fieldSet->description) && !empty($fieldSet->description)) : ?>
				<div class="tab-description alert alert-info">
					<span class="icon-info" aria-hidden="true"></span> <?php echo Text::_($fieldSet->description); ?>
				</div>
			<?php endif; ?>
			<?php foreach ($this->providerForm->getFieldset($name) as $field) : ?>
				<?php
				$groupClass = $field->type === 'Spacer' ? ' field-spacer' : '';
				?>
				<?php if ($field->hidden) : ?>
					<?php echo $field->input; ?>
				<?php else : ?>
					<div class="control-group<?php echo $groupClass; ?>">
						<div class="control-label">
							<?php echo $field->label; ?>
						</div>
						<div class="controls">
							<?php echo $field->input; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</div>
