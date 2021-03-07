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
<div id="charmap" role="presentation">
	<h3><?php echo JText::_('WF_ADVANCED_CHARMAP_TITLE'); ?></h3>
	<div id="charmapView"><!-- Chars will be rendered here --></div>
	<div id="charmapDescription">
		<div id="codeV"></div>
		<div id="codeN"></div>
		<div class="box">
			<div class="title">HTML-Code</div>
			<div id="codeA"></div>
		</div>
		<div class="box">
			<div class="title">NUM-Code</div>
			<div id="codeB"></div>
		</div>
	</div>
</div>