<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.beez3
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$app = JFactory::getApplication();
$templateparams = $app->getTemplate(true)->params;

$class  = ' class="first"';
$user   = JFactory::getUser();
$groups = $user->getAuthorisedViewLevels();
?>

<?php if (count($this->children[$this->category->id]) > 0) : ?>

	<ul>
	<?php foreach ($this->children[$this->category->id] as $id => $child) : ?>
		<?php // Check whether category access level allows access to subcategories. ?>
		<?php if (in_array($child->access, $groups)) : ?>
			<?php
			if ($child->numitems || $this->params->get('show_empty_categories') || count($child->getChildren())) :
				if (!isset($this->children[$this->category->id][$id + 1])) :
					$class = ' class="last"';
				endif;
			?>
				<li<?php echo $class; ?>>
				<?php $class = ''; ?>
					<span class="item-title"><a href="<?php echo JRoute::_(ContentHelperRoute::getCategoryRoute($child->id));?>">
						<?php echo $this->escape($child->title); ?></a>
					</span>

					<?php if ($this->params->get('show_subcat_desc') == 1) :?>
						<?php if ($child->description) : ?>
							<div class="category-desc">
								<?php echo JHtml::_('content.prepare', $child->description, '', 'com_content.category'); ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $this->params->get('show_cat_num_articles', 1)) : ?>
						<dl>
							<dt>
								<?php echo JText::_('COM_CONTENT_NUM_ITEMS'); ?>
							</dt>
							<dd>
								<?php echo $child->getNumItems(true); ?>
							</dd>
						</dl>
					<?php endif; ?>

					<?php if (count($child->getChildren()) > 0):
						$this->children[$child->id] = $child->getChildren();
						$this->category = $child;
						$this->maxLevel--;
						if ($this->maxLevel !== 0) :
							echo $this->loadTemplate('children');
						endif;
						$this->category = $child->getParent();
						$this->maxLevel++;
					endif; ?>
				</li>
			<?php endif; ?>
		<?php endif; ?>
	<?php endforeach; ?>
	</ul>

<?php endif; ?>
