<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen');

$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$loggeduser = JFactory::getUser();
$saveOrder = $listOrder === 'profiles.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_sso&task=profiles.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'profilesList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="index.php?option=com_sso&view=profiles" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped" id="profilesList">
				<thead>
				<tr>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', '', 'profiles.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
					</th>
					<th width="1%" class="nowrap center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th width="1%" class="nowrap center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'profiles.published', $listDirn, $listOrder); ?>
					</th>
					<th class="left">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_SSO_PROFILES_NAME', 'profiles.name', $listDirn, $listOrder); ?>
					</th>
                    <th class="left">
						<?php echo Text::_('COM_SSO_PROFILES_ALIAS'); ?>
                    </th>
                    <th class="left">
						<?php echo Text::_('COM_SSO_PROFILES_METADATA_URL'); ?>
                    </th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="15">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>
				<?php
				$canEdit   = $this->canDo->get('core.edit');
				$canChange = $loggeduser->authorise('core.edit.state','com_sso');

				foreach ($this->items as $i => $item) :
					?>
					<tr>
						<td class="order nowrap center hidden-phone">
							<?php
							$iconClass = '';

							if (!$canChange)
							{
								$iconClass = ' inactive';
							}
							elseif (!$saveOrder)
							{
								$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::tooltipText('JORDERINGDISABLED');
							}
							?>
							<span class="sortable-handler <?php echo $iconClass ?>">
									<span class="icon-menu"></span>
								</span>
							<?php if ($canChange && $saveOrder) : ?>
								<input type="text" style="display:none" name="order[]" size="5"
								       value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
							<?php endif; ?>
						</td>
						<td class="center">
							<?php if ($canEdit || $canChange) : ?>
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							<?php endif; ?>
						</td>
						<td class="center">
							<div class="btn-group">
								<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'profiles.', $canChange); ?>
							</div>
						</td>
						<td>
							<div class="name break-word">
								<?php if ($canEdit) : ?>
									<a href="<?php echo Route::_('index.php?option=com_sso&task=profile.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::sprintf('COM_SSO_EDIT_PROFILE', $this->escape($item->name)); ?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
							</div>
						</td>
                        <td>
							<?php echo $item->alias; ?>
                        </td>
                        <td>
                            <?php $url = Uri::root() . $this->config->get('baseurlpath') . 'module.php/saml/sp/metadata.php/' . $item->alias; ?>
                            <?php echo HTMLHelper::_('link', $url, $url, 'target="_blank"'); ?>
                        </td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
