<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/** @var JPaginationObject $item */
$item = $displayData['data'];

$display = $item->text;
$icon    = null;

switch ((string) $item->text)
{
	// Check for "Start" item
	case JText::_('JLIB_HTML_START') :
		$icon = 'icon-first';
		break;

	// Check for "Prev" item
	case JText::_('JPREV') :
		$icon = 'icon-previous';
		break;

	// Check for "Next" item
	case JText::_('JNEXT') :
		$icon = 'icon-next';
		break;

	// Check for "End" item
	case JText::_('JLIB_HTML_END') :
		$icon = 'icon-last';
		break;
}

if ($icon !== null)
{
	$display = '<span class="' . $icon . '"></span>';
}

if ($displayData['active'])
{
	$attribs = ['class' => 'pagenav'];

	if (!is_numeric($item->text))
	{
		JHtml::_('bootstrap.tooltip');
		$attribs['class'] .= ' hasTooltip';
		$attribs['title'] = $item->text;
	}
}
?>
<?php if ($displayData['active']) : ?>
	<li>
		<?php echo JHtml::_('link', $item->link, $display, $attribs); ?>
	</li>
<?php else : ?>
	<li class="<?php echo (property_exists($item, 'active') && $item->active) ? 'active' : 'disabled'; ?>">
		<span><?php echo $display; ?></span>
	</li>
<?php endif;
