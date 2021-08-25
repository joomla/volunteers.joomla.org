<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
			<dd class="category-name">
				<?php $title = $this->escape($displayData['item']->category_title); ?>
				<?php if ($displayData['params']->get('link_category') && $displayData['item']->catslug) : ?>
					<?php $url = '<a href="' . JRoute::_(ContentHelperRoute::getCategoryRoute($displayData['item']->catslug)) . '" itemprop="genre">' . $title . '</a>'; ?>
					<?php echo JText::sprintf('COM_CONTENT_CATEGORY', $url); ?>
				<?php else : ?>
					<?php echo JText::sprintf('COM_CONTENT_CATEGORY', '<span itemprop="genre">' . $title . '</span>'); ?>
				<?php endif; ?>
			</dd>