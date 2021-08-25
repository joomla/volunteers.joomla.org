<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$class = ' class="first"';
if ($this->maxLevel != 0 && count($this->children[$this->category->id]) > 0) :
?>
<ul>
<?php foreach ($this->children[$this->category->id] as $id => $child) : ?>
	<?php
	if ($child->numitems || $this->params->get('show_empty_categories') || count($child->getChildren())) :
	if (!isset($this->children[$this->category->id][$id + 1]))
	{
		$class = ' class="last"';
	}
	?>
	<li<?php echo $class; ?>>
		<?php $class = ''; ?>
			<span class="item-title"><a href="<?php echo JRoute::_(ContactHelperRoute::getCategoryRoute($child->id));?>">
				<?php echo $this->escape($child->title); ?></a>
			</span>

			<?php if ($this->params->get('show_subcat_desc') == 1) :?>
			<?php if ($child->description) : ?>
				<div class="category-desc">
					<?php echo JHtml::_('content.prepare', $child->description, '', 'com_contact.category'); ?>
				</div>
			<?php endif; ?>
			<?php endif; ?>

			<?php if ($this->params->get('show_cat_items') == 1) :?>
				<dl><dt>
					<?php echo JText::_('COM_CONTACT_CAT_NUM'); ?></dt>
					<dd><?php echo $child->numitems; ?></dd>
				</dl>
		<?php endif; ?>
			<?php if (count($child->getChildren()) > 0 ) :
				$this->children[$child->id] = $child->getChildren();
				$this->category = $child;
				$this->maxLevel--;
				echo $this->loadTemplate('children');
				$this->category = $child->getParent();
				$this->maxLevel++;
			endif; ?>
		</li>
	<?php endif; ?>
	<?php endforeach; ?>
	</ul>
<?php endif;
