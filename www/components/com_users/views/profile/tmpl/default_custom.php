<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::register('users.spacer', array('JHtmlUsers', 'spacer'));

$fieldsets = $this->form->getFieldsets();

if (isset($fieldsets['core']))
{
	unset($fieldsets['core']);
}

if (isset($fieldsets['params']))
{
	unset($fieldsets['params']);
}

$tmp          = isset($this->data->jcfields) ? $this->data->jcfields : array();
$customFields = array();

foreach ($tmp as $customField)
{
	$customFields[$customField->name] = $customField;
}

?>
<?php foreach ($fieldsets as $group => $fieldset) : ?>
	<?php $fields = $this->form->getFieldset($group); ?>
	<?php if (count($fields)) : ?>
		<fieldset id="users-profile-custom-<?php echo $group; ?>" class="users-profile-custom-<?php echo $group; ?>">
			<?php if (isset($fieldset->label) && ($legend = trim(JText::_($fieldset->label))) !== '') : ?>
				<legend><?php echo $legend; ?></legend>
			<?php endif; ?>
			<?php if (isset($fieldset->description) && trim($fieldset->description)) : ?>
				<p><?php echo $this->escape(JText::_($fieldset->description)); ?></p>
			<?php endif; ?>
			<dl class="dl-horizontal">
				<?php foreach ($fields as $field) : ?>
					<?php if (!$field->hidden && $field->type !== 'Spacer') : ?>
						<dt>
							<?php echo $field->title; ?>
						</dt>
						<dd>
							<?php if (key_exists($field->fieldname, $customFields)) : ?>
								<?php echo strlen($customFields[$field->fieldname]->value) ? $customFields[$field->fieldname]->value : JText::_('COM_USERS_PROFILE_VALUE_NOT_FOUND'); ?>
							<?php elseif (JHtml::isRegistered('users.' . $field->id)) : ?>
								<?php echo JHtml::_('users.' . $field->id, $field->value); ?>
							<?php elseif (JHtml::isRegistered('users.' . $field->fieldname)) : ?>
								<?php echo JHtml::_('users.' . $field->fieldname, $field->value); ?>
							<?php elseif (JHtml::isRegistered('users.' . $field->type)) : ?>
								<?php echo JHtml::_('users.' . $field->type, $field->value); ?>
							<?php else : ?>
								<?php echo JHtml::_('users.value', $field->value); ?>
							<?php endif; ?>
						</dd>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
		</fieldset>
	<?php endif; ?>
<?php endforeach; ?>
