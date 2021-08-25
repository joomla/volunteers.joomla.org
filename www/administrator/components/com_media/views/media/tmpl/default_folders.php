<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Set up the sanitised target for the ul
$ulTarget = str_replace('/', '-', $this->folders['data']->relative);

?>
<ul class="nav nav-list" id="collapseFolder-<?php echo $ulTarget; ?>">
<?php if (isset($this->folders['children'])) :
	foreach ($this->folders['children'] as $folder) :
	// Get a sanitised name for the target
	$target = str_replace('/', '-', $folder['data']->relative); ?>
	<li id="<?php echo $target; ?>" class="folder">
		<a href="index.php?option=com_media&amp;view=mediaList&amp;tmpl=component&amp;folder=<?php echo rawurlencode($folder['data']->relative); ?>" target="folderframe" class="folder-url" >
			<span class="icon-folder"></span>
			<?php echo $this->escape($folder['data']->name); ?>
		</a>
		<?php echo $this->getFolderLevel($folder); ?>
	</li>
<?php endforeach;
endif; ?>
</ul>
