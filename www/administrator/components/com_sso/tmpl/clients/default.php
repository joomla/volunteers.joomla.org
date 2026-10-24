<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Rolandd\Component\Sso\Administrator\View\Clients\HtmlView;

defined('_JEXEC') or die;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns');

$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$loggeduser = $this->getCurrentUser();
$saveOrder  = $listOrder === 'clients.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_sso&task=clients.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'clientsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<form action="index.php?option=com_sso&view=clients" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="clientsList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_SSO_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', '', 'clients.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
                            </th>
                            <td class="w-1 text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th scope="col" class="w-1 text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'clients.published', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" style="min-width:100px">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SSO_CLIENTS_NAME', 'clients.name', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo Text::_('COM_SSO_CLIENTS_URL'); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo Text::_('COM_SSO_CLIENTS_OUTPUTDIR'); ?>
                            </th>
                            <th scope="col" class="w-10 d-none d-md-table-cell">
                                <?php echo Text::_('COM_SSO_CLIENTS_FORMAT'); ?>
                            </th>
                        </tr>
                        </thead>
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
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'clients.', $canChange); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="name break-word">
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_sso&task=client.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::sprintf('COM_SSO_EDIT_CLIENT', $this->escape($item->name)); ?>">
                                                <?php echo $this->escape($item->name); ?></a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->name); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $item->source; ?>
                                </td>
                                <td>
                                    <?php echo $item->outputDir; ?>
                                </td>
                                <td>
                                    <?php echo $item->outputFormat; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
            </div>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>
