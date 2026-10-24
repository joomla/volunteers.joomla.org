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
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Rolandd\Component\Sso\Administrator\View\Client\HtmlView;

defined('_JEXEC') or die;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_sso&layout=edit&id=' . $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<?php echo $this->form->renderFieldset('settings'); ?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="view" value="client" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
