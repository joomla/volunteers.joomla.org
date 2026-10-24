<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Databasefilters\HtmlView $this */

echo $this->loadAnyTemplate('commontemplates/errormodal');
echo $this->loadAnyTemplate('commontemplates/profilename');

?>
<div class="border row row-cols-lg-auto g-3 align-items-center my-3 mx-1 pb-3">

	<div class="col-12">
		<label>
			<?= Text::_('COM_AKEEBABACKUP_DBFILTER_LABEL_ROOTDIR') ?>
		</label>
	</div>
	<div class="col-12">
		<span><?= $this->root_select ?></span>
	</div>


	<div class="col-12">
		<button type="button"
				class="btn btn-success" id="comAkeebaDatabasefiltersExcludeNonCMS">
			<span class="fa fa-flag"></span>
			<?= Text::_('COM_AKEEBABACKUP_DBFILTER_LABEL_EXCLUDENONCORE') ?>
		</button>
	</div>

	<div class="col-12">
		<button type="button"
				class="btn btn-danger" id="comAkeebaDatabasefiltersNuke">
			<span class="fa fa-radiation"></span>
			<?= Text::_('COM_AKEEBABACKUP_DBFILTER_LABEL_NUKEFILTERS') ?>
		</button>
	</div>
</div>

<div id="ak_main_container" class="row row-cols-1">
	<div class="col">
		<div class="card">
			<h3 class="card-header">
				<?= Text::_('COM_AKEEBABACKUP_DBFILTER_LABEL_TABLES') ?>
			</h3>
			<div id="tables" class="card-body overflow-scroll" style="height: 45vh;"></div>
		</div>
	</div>
</div>
