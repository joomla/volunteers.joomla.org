<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\HTML\HTMLHelper as HTMLHelperAlias;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var  \Akeeba\Backup\Admin\View\Alice\Html $this */

$js = <<< JS
akeeba.System.documentReady(function(){
	window.setTimeout(function() {
	  document.forms.adminForm.submit()
	}, 500);
});

JS;
$this->container->template->addJSInline($js)
?>

<div class="akeeba-panel--info">
	<header class="akeeba-block-header">
		<h3>
			@lang('COM_AKEEBA_ALICE_ANALYZE_LABEL_PROGRESS')
		</h3>
	</header>
	<h4>
		{{ $this->currentSection }}
	</h4>
	<p>
		{{ $this->currentCheck }}
	</p>
	<div class="akeeba-progress">
		<div class="akeeba-progress-fill" style="width:{{ $this->percentage }}%;"></div>
		<div class="akeeba-progress-status">
			{{ $this->percentage }}%
		</div>
	</div>
	<p>
		<img src="@media('media://com_akeeba/icons/spinner.gif')"
			 alt="@lang('COM_AKEEBA_ALICE_ANALYZE_LABEL_PROGRESS')" />
	</p>
</div>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input name="option" value="com_akeeba" type="hidden" />
	<input name="view" value="Alice" type="hidden" />
	<input name="task" value="step" type="hidden" />
	<input type="hidden" name="@token(true)" value="1" />
</form>