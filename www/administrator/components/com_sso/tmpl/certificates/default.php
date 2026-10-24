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
use Joomla\CMS\WebAsset\WebAssetManager;
use Rolandd\Component\Sso\Administrator\View\Certificates\HtmlView;

defined('_JEXEC') or die;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns');

?>
<form action="index.php?option=com_sso&view=clients" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <?php echo Text::sprintf('COM_SSO_FOUND_CERTIFICATES', '<span style="font-weight: bold;">' . PATH_SIMPLESAMLPHP . '/cert</span>'); ?>
                    <table class="table table-striped" id="certificatesList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_SSO_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <td class="w-1 text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </td>
                            <th scope="col" class="w-1">
                                <?php echo Text::_('COM_SSO_CERTIFICATES_PEM'); ?>
                            </th>
                            <th scope="col" class="w-1">
                                <?php echo Text::_('COM_SSO_CERTIFICATES_CRT'); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $canEdit   = $this->canDo->get('core.edit');

                        foreach ($this->items as $i => $item) :
                            ?>
                            <tr>
                                <td class="text-center">
                                    <?php if ($canEdit) : ?>
                                        <?php echo HTMLHelper::_('grid.id', $i, $i); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $item['pem'] ?? ''; ?>
                                </td>
                                <td>
                                    <?php echo $item['crt'] ?? ''; ?>
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
        </div>
	</div>
</form>
