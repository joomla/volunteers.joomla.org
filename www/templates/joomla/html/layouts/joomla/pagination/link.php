<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Joomla\CMS\Pagination\PaginationObject $item */
$item = $displayData['data'];

$display = $item->text;
$icon    = null;

switch ((string) $item->text)
{
	// Check for "Start" item
	case Text::_('JLIB_HTML_START') :
		$icon = 'icon-first';
		break;

	// Check for "Prev" item
	case Text::_('JPREV') :
		$icon = 'icon-previous';
		break;

	// Check for "Next" item
	case Text::_('JNEXT') :
		$icon = 'icon-next';
		break;

	// Check for "End" item
	case Text::_('JLIB_HTML_END') :
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
		HTMLHelper::_('bootstrap.tooltip');
		$attribs['class'] .= ' hasTooltip';
		$attribs['title'] = $item->text;
	}
	else
	{
		$attribs['class'] .= ' hidden-phone';
	}
}
?>
<?php if ($displayData['active']) : ?>
	<li>
		<?php echo HTMLHelper::_('link', $item->link, $display, $attribs); ?>
	</li>
<?php else : ?>
	<li class="<?php echo (property_exists($item, 'active') && $item->active) ? 'active' : 'disabled'; ?><?php echo $icon === null ? ' hidden-phone' : ''; ?>">
		<span><?php echo $display; ?></span>
	</li>
<?php endif;
