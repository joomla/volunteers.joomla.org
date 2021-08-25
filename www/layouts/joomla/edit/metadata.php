<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$form = $displayData->getForm();

// JLayout for standard handling of metadata fields in the administrator content edit screens.
$fieldSets = $form->getFieldsets('metadata');
?>

<?php foreach ($fieldSets as $name => $fieldSet) : ?>
	<?php if (isset($fieldSet->description) && trim($fieldSet->description)) : ?>
		<p class="alert alert-info"><?php echo $this->escape(JText::_($fieldSet->description)); ?></p>
	<?php endif; ?>

	<?php
	// Include the real fields in this panel.
	if ($name === 'jmetadata')
	{
		echo $form->renderField('metadesc');
		echo $form->renderField('metakey');
		echo $form->renderField('xreference');
	}

	foreach ($form->getFieldset($name) as $field)
	{
		if ($field->name !== 'jform[metadata][tags][]')
		{
			echo $field->renderField();
		}
	} ?>
<?php endforeach; ?>
