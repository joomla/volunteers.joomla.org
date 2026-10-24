<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\HTML\Helpers\UiTab;
use Joomla\CMS\Language\Text;
use Joomla\CMS\WebAsset\WebAssetManager;
use Rolandd\Component\Sso\Administrator\View\Config\HtmlView;

defined('_JEXEC') or die;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('form.validate')
    ->useScript('keepalive');

?>
<form action="index.php?option=com_sso&view=config" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php
                echo UiTab::startTabSet('sso', ['active' => 'configTabs']);

                foreach ($this->form->getFieldsets() as $name => $fieldSet) {
                    $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label;
                    echo UiTab::addTab('sso', $name, Text::_($label));
                        if ($name === 'csp') {
                            ?><div><?php
                            echo Text::_('COM_SSO_CSP_DESCRIPTION');
                            ?></div><?php
                        }
                        echo $this->form->renderFieldset($name);
                    echo UiTab::endTab();
                }

                echo UiTab::endTabSet();
                ?>
            </div>
        </div>
        <?php echo $this->form->renderControlFields(); ?>
    </div>
</form>
