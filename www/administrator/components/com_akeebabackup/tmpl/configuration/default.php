<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Configuration\HtmlView $this */

// Enable Bootstrap popovers
HTMLHelper::_('bootstrap.popover', '[rel=popover]', [
	'html'      => true,
	'placement' => 'bottom',
	'trigger'   => 'click hover',
	'sanitize'  => false,
]);

// Configuration Wizard pop-up
if ($this->promptForConfigurationwizard)
{
	echo $this->loadAnyTemplate('Configuration/confwiz_modal');
}

// Modal dialog prototypes
echo $this->loadAnyTemplate('commontemplates/ftpconnectiontest');
echo $this->loadAnyTemplate('commontemplates/errormodal');
echo $this->loadAnyTemplate('commontemplates/folderbrowser');
?>

<?php if($this->secureSettings == 1): ?>
    <div class="alert alert-success alert-dismissible">
		<?= Text::_('COM_AKEEBABACKUP_CONFIG_UI_SETTINGS_SECURED') ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
    </div>
<?php elseif($this->secureSettings == 0): ?>
    <div class="alert alert-warning alert-dismissible">
	    <?= Text::_('COM_AKEEBABACKUP_CONFIG_UI_SETTINGS_NOTSECURED') ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
    </div>
<?php endif ?>

<?= $this->loadAnyTemplate('commontemplates/profilename') ?>

<div class="alert alert-info alert-dismissible">
	<?= Text::_('COM_AKEEBABACKUP_CONFIG_WHERE_ARE_THE_FILTERS') ?>
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
</div>

<form name="adminForm" id="adminForm" method="post"
	  action="<?= Route::_('index.php?option=com_akeebabackup&view=Configuration') ?>">

	<div class="card">
		<h3 class="card-header">
			<?= Text::_('COM_AKEEBABACKUP_PROFILES_LABEL_DESCRIPTION') ?>
		</h3>

		<div class="card-body">
			<div class="row mb-3">
				<label for="profilename" class="col-sm-3 col-form-label"
					   rel="popover"
					   title="<?= Text::_('COM_AKEEBABACKUP_PROFILES_LABEL_DESCRIPTION') ?>"
					   data-bs-content="<?= Text::_('COM_AKEEBABACKUP_PROFILES_LABEL_DESCRIPTION_TOOLTIP') ?>"
				>
					<?= Text::_('COM_AKEEBABACKUP_PROFILES_LABEL_DESCRIPTION') ?>
				</label>
				<div class="col-sm-9">
					<input type="text" name="profilename" id="profilename"
						   class="form-control"
						   value="<?= $this->escape($this->profileName) ?>"/>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-sm-9 offset-sm-3">
					<div class="form-check">
						<input type="checkbox" name="quickicon"
							   class="form-check-input"
							   id="quickicon" <?= $this->quickIcon ? 'checked="checked"' : '' ?>/>
						<label for="quickicon"
							   class="form-check-label"
							   rel="popover"
							   title="<?= Text::_('COM_AKEEBABACKUP_CONFIG_QUICKICON_LABEL') ?>"
							   data-bs-content="<?= Text::_('COM_AKEEBABACKUP_CONFIG_QUICKICON_DESC') ?>"
						>
							<?= Text::_('COM_AKEEBABACKUP_CONFIG_QUICKICON_LABEL') ?>
						</label>
					</div>
				</div>
			</div>
		</div>
    </div>

    <!-- This div contains dynamically generated user interface elements -->
    <div id="akeebagui">
    </div>

	<input type="hidden" name="task" value=""/>
	<?= HTMLHelper::_('form.token') ?>
</form>
