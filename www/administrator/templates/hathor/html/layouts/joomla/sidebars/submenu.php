<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<div id="sidebar">
	<div class="sidebar-nav">
		<?php if ($displayData->displayMenu) : ?>
		<ul id="submenu" class="nav nav-list">
			<?php foreach ($displayData->list as $item) :
			if (isset ($item[2]) && $item[2] == 1) : ?>
				<li class="active">
			<?php else : ?>
				<li>
			<?php endif;
			if ($displayData->hide) : ?>
				<a class="nolink"><?php echo $item[0]; ?></a>
			<?php else :
				if ($item[1] !== '') : ?>
					<a href="<?php echo JFilterOutput::ampReplace($item[1]); ?>"><?php echo $item[0]; ?></a>
				<?php else : ?>
					<?php echo $item[0]; ?>
				<?php endif;
			endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>
