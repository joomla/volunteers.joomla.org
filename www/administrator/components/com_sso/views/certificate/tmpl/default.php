<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

// Load the tooltip behavior.
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>
<form action="<?php echo Route::_('index.php?option=com_sso'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php if (count($this->certificates) > 0) : ?>
			<?php echo Text::sprintf('COM_SSO_FOUND_CERTIFICATES', JPATH_LIBRARIES . '/simplesamlphp/cert'); ?>
			<ul>
				<?php
				foreach ($this->certificates as $certificate)
				{
					?><li><?php echo $certificate; ?></li><?php
				}
				?>
			</ul>
		<?php else: ?>
			<?php echo Text::_('COM_SSO_FOUND_NO_CERTIFICATES'); ?>
		<?php endif; ?>
		<hr />
		<?php echo $this->form->renderFieldset('certificate'); ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="view" value="certificate" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
