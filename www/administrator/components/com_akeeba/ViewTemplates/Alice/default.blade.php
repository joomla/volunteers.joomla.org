<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\Backup\Admin\View\Alice\Html $this */
?>
@if(empty($this->logs))
	<div class="akeeba-block--failure">
		<p>@lang('COM_AKEEBA_LOG_NONE_FOUND')</p>
	</div>
	<?php return; ?>
@endif


<form name="adminForm" id="adminForm" action="index.php?option=com_akeeba&view=Alice&task=start"
	  method="post" class="akeeba-form--inline">
	@if($this->autorun)
		<div class="akeeba-block--warning">
			<p>
				@lang('ALICE_AUTORUN_NOTICE')
			</p>
		</div>
	@endif

	<div class="akeeba-form-group">
		<label for="tag">
			@lang('COM_AKEEBA_LOG_CHOOSE_FILE_TITLE')
		</label>
		@jhtml('select.genericlist', $this->logs, 'log', [], 'value', 'text', $this->log)
	</div>

	<div class="akeeba-form-group--actions">
		<button class="akeeba-btn--primary" id="analyze-log" type="submit">
			<span class="akion-ios-analytics"></span>
			@lang('COM_AKEEBA_ALICE_ANALYZE')
		</button>
	</div>

	<div class="akeeba-hidden-fields-container">
		<input type="hidden" name="@token(true)" value="1" />
	</div>
</form>