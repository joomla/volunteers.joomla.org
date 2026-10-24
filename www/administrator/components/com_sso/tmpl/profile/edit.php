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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Rolandd\Component\Sso\Administrator\View\Profile\HtmlView;

defined('_JEXEC') or die;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useStyle('com_sso.core');

?>
<form action="<?php echo Route::_('index.php?option=com_sso&layout=edit&id=' . $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    <div class="row title-alias form-vertical mb-3">
        <div class="col-12 col-md-6">
            <?php echo $this->form->renderField('protocol'); ?>
        </div>
        <div class="col-12 col-md-6">
            <?php echo $this->form->renderField('published'); ?>
        </div>
    </div>
	<hr />
    <?php
    echo UiTab::startTabSet('profile', ['active' => 'authorization_' . $this->item->protocol]);

    echo UiTab::addTab('profile', 'authorization_saml', Text::_('COM_SSO_TAB_PROFILE_SAML'));
    echo $this->form->renderFieldSet('authorization_saml');
    echo UiTab::endTab();

    echo UiTab::addTab('profile', 'authorization_ldap', Text::_('COM_SSO_TAB_PROFILE_LDAP'));
    echo $this->form->renderFieldSet('authorization_ldap');
    echo UiTab::endTab();

    echo UiTab::addTab('profile', 'joomla', Text::_('COM_SSO_TAB_PROFILE_JOOMLA'));
    echo $this->form->renderFieldSet('joomla');
    echo UiTab::endTab();

    echo UiTab::addTab('profile', 'fields', Text::_('COM_SSO_TAB_PROFILE_FIELDS'));
    echo $this->form->renderFieldSet('fields');
    echo UiTab::endTab();

    echo UiTab::endTabSet();
    ?>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="view" value="profile" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
