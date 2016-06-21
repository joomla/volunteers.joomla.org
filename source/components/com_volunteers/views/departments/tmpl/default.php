<?php
/*
* @package		Joomla! Volunteers
* @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// No direct access.
defined('_JEXEC') or die;

$compParams = $this->get('ComponentConfiguration');
;?>
<div class="row-fluid">
	<div class="filter-bar">

	</div>
	<h1><?php echo JText::_('COM_VOLUNTEERS_PAGETITLE_DEPARTMENT') ?></h1>
</div>

<?php if(trim($compParams->get('department_pretext')) != '' ) :?>
	<div class="row-fluid">
		<?php echo $compParams->get('department_pretext');?>
	</div>
<?php endif; ?>

<?php if(empty($this->items)) :?>
	<div class="row-fluid">
		<?php echo JText::_('COM_VOLUNTEERS_NOITEMS');?>
	</div>
<?php else: ?>
	<?php $chunks = array_chunk($this->items, 2);?>
	<?php foreach ($chunks as $row) : ?>
		<div class="row-fluid">
			<?php foreach ($row as $item) : ?>
				<div class="span6">
					<div class="well well-small">
						<h4 class="text-center">
							<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=department&id='.$item->volunteers_department_id)?>">
								<?php echo($item->title);?>
							</a>
						</h4>
						<p>
							<?php echo($item->intro);?>
						</p>

					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
