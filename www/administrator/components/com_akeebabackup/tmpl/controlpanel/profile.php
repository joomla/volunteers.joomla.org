<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

/**
 * Call this template with:
 * [
 * 	'returnURL' => 'index.php?......'
 * ]
 * to set up a custom return URL
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$this->getDocument()->getWebAssetManager()
	->usePreset('choicesjs')
	->useScript('webcomponent.field-fancy-select');
?>
<div class="akeeba-panel">
	<form action="<?= Route::_('index.php?option=com_akeebabackup&task=Controlpanel.Switchprofile') ?>" method="post"
		  name="switchActiveProfileForm" id="switchActiveProfileForm"
		  class="akeebabackup-profile-switch-container d-md-flex flex-md-row justify-content-md-evenly align-items-center border border-1 bg-light border-rounded rounded-2 mt-1 mb-2 p-2">
		<?php if(isset($returnURL)): ?>
		<input type="hidden" name="returnurl" value="<?= $this->escape($returnURL) ?>" />
		<?php endif ?>
		<?= HTMLHelper::_('form.token') ?>

		<div class="m-2">
			<label>
				<?= Text::_('COM_AKEEBABACKUP_CPANEL_PROFILE_TITLE') ?>: #<?= (int)$this->profileId ?>
			</label>
		</div>
		<div class="flex-grow-1">
			<joomla-field-fancy-select
					search-placeholder="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_PROFILEID') ?>"
			><?=
				HTMLHelper::_('select.genericlist', $this->profileList, 'profileid', [
						'list.select' => $this->profileId,
						'id' => 'comAkeebaControlPanelProfileSwitch',
				])
			?></joomla-field-fancy-select>
		</div>

		<div class="m-2">
			<button type="button"
					class="btn btn-primary btn-sm d-xs-none d-sm-none" type="submit">
				<span class="akion-forward"></span>
				<?= Text::_('COM_AKEEBABACKUP_CPANEL_PROFILE_BUTTON') ?>
			</button>
		</div>
	</form>
</div>
