<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

// Load the tooltip behavior.
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen');

Factory::getDocument()->addScriptDeclaration(<<<JS
	Joomla.submitbutton = function(task)
	{
		if (task === 'client.cancel' || document.formvalidator.isValid(document.getElementById('adminForm')))
		{
			Joomla.submitform(task, document.getElementById('adminForm'), false);
		}
	};
JS
);

?>
<form action="<?php echo Route::_('index.php?option=com_sso&layout=edit&id=' . $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<?php echo $this->form->renderFieldset('settings'); ?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="view" value="client" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
