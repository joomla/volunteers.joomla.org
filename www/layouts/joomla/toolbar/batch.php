<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.core');

$title = $displayData['title'];
JText::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
$message = "alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));";
?>
<button type="button" data-toggle="modal" onclick="if (document.adminForm.boxchecked.value==0){<?php echo $message; ?>}else{jQuery( '#collapseModal' ).modal('show'); return true;}" class="btn btn-small">
	<span class="icon-checkbox-partial" aria-hidden="true"></span>
	<?php echo $title; ?>
</button>
