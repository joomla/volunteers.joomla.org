<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\Helpers\UiTab;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// phpcs:disable
?>
<?php
echo UiTab::startTabSet('sso', ['active' => 'configTabs']);

foreach ($this->providerForm->getFieldsets() as $name => $fieldSet) {
    $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label;
    echo UiTab::addTab('sso', $name, Text::_($label));
        echo $this->providerForm->renderFieldset($name);
    echo UiTab::endTab();
}

echo UiTab::endTabSet();
?>
