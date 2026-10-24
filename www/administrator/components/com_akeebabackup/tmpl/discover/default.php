<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Discover\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$this->getDocument()
	->addScriptOptions('akeebabackup.Configuration.URLs.browser', Route::_(
		'index.php?option=com_akeebabackup&view=Browser&processfolder=1&tmpl=component&folder=',
		false, Route::TLS_IGNORE, true
	));

echo $this->loadAnyTemplate('commontemplates/folderbrowser');
?>

<div class="alert alert-info">
    <p>
		<?= Text::sprintf('COM_AKEEBABACKUP_DISCOVER_LABEL_S3IMPORT', 'index.php?option=com_akeebabackup&view=S3import') ?>
    </p>
    <p>
        <a class="btn btn-primary btn-sm" href="index.php?option=com_akeebabackup&view=S3import">
            <span class="fa fa-cloud-download-alt"></span>
            <?= Text::_('COM_AKEEBABACKUP_S3IMPORT') ?>
        </a>
    </p>
</div>

<form name="adminForm" id="adminForm"
	  action="<?= Route::_('index.php?option=com_akeebabackup&task=Discover.discover') ?>"
	  method="post">

    <div class="row mb-3">
        <label for="directory" class="col-sm-3 col-form-label">
            <?= Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_DIRECTORY') ?>
        </label>
		<div class="col-sm-9">
			<div class="input-group">
				<input type="text" name="directory" id="directory" class="form-control"
					   value="<?= $this->escape($this->directory) ?>" />
				<button type="button"
						class="btn btn-dark" id="browsebutton">
					<span class="fa fa-folder-open"></span>
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_UI_BROWSE') ?>
				</button>
			</div>
			<p class="form-text">
				<?= Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_SELECTDIR') ?>
			</p>
		</div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-9 col-sm-offset-3">
            <button type="submit"
					class="btn btn-primary">
				<span class="fa fa-search"></span>
                <?= Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_SCAN') ?>
            </button>
        </div>
    </div>

	<?= HTMLHelper::_('form.token') ?>
</form>
