<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Filefilters\HtmlView $this */

echo $this->loadAnyTemplate('commontemplates/errormodal');
echo $this->loadAnyTemplate('commontemplates/profilename');

?>
<div class="border row row-cols-lg-auto g-3 align-items-center my-3 mx-1 pb-3">
    <div class="col-12">
        <label>
            <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_ROOTDIR') ?>
        </label>
    </div>
	<div class="col-12">
		<span><?= $this->root_select ?></span>
	</div>
    <div class="col-12">
        <button type="button"
				class="btn btn-danger" id="comAkeebaFilefiltersNuke">
            <span class="fa fa-trash"></span>
            <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_NUKEFILTERS') ?>
        </button>
	</div>
	<div class="col-12">
        <a class="btn btn-secondary text-decoration-none"
		   href="<?= Route::_('index.php?option=com_akeebabackup&view=Filefilters&layout=tabular') ?>">
            <span class="fa fa-list-ul"></span>
	        <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_VIEWALL') ?>
        </a>
    </div>
</div>

<nav aria-label="breadcrumb" id="ak_crumbs_container" class="">
	<ol id="ak_crumbs" class="breadcrumb border my-3 p-3"></ol>
</nav>

<div id="ak_main_container" class="row row-cols-1 row-cols-lg-2 g-3">
    <div class="col">
        <div class="card">
			<h3 class="card-header">
		        <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_DIRS') ?>
			</h3>
            <div id="folders" class="card-body overflow-scroll" style="height: 45vh;"></div>
        </div>
    </div>

	<div class="col">
        <div class="card">
			<h3 class="card-header">
		        <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_FILES') ?>
			</h3>
            <div id="files" class="card-body overflow-scroll" style="height: 45vh;"></div>
        </div>
    </div>
</div>
