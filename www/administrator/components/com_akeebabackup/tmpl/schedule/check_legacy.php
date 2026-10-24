<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Schedule\HtmlView $this */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<div class="card mb-3">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDCHECK') ?>
	</h3>

	<div class="card-body">

		<?php if(!$this->checkinfo->info->legacyapi): ?>
			<div class="alert alert-danger">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_LEGACYAPI_DISABLED') ?>
				</p>
				<p>
					<a href="<?= $this->enableLegacyFrontendURL ?>" class="btn btn-primary">
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_BTN_ENABLE_LEGACYAPI') ?>
					</a>
				</p>
			</div>
		<?php elseif(!trim($this->checkinfo->info->secret)): ?>
			<div class="alert alert-danger">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_SECRET') ?>
				</p>
				<p>
					<a href="<?= $this->resetSecretWordURL ?>" class="btn btn-primary">
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_BTN_RESET_SECRETWORD') ?>
					</a>
				</p>
			</div>
		<?php else: ?>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDBACKUP_MANYMETHODS') ?>
			</p>

			<h4>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDBACKUP_TAB_WEBCRON', true) ?>
			</h4>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON') ?>
			</p>

			<table class="table table-striped w-100">
				<tr>
					<td></td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_INFO') ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_NAME') ?>
					</td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_NAME_INFO') ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_TIMEOUT') ?>
					</td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_TIMEOUT_INFO') ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_URL') ?>
					</td>
					<td>
						<?= $this->escape($this->checkinfo->frontend->path) ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_LOGIN') ?>
					</td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_LOGINPASSWORD_INFO') ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_PASSWORD') ?>
					</td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_LOGINPASSWORD_INFO') ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_EXECUTIONTIME') ?>
					</td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_EXECUTIONTIME_INFO') ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_ALERTS') ?>
					</td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_ALERTS_INFO') ?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WEBCRON_THENCLICKSUBMIT') ?>
					</td>
				</tr>
			</table>

			<h4>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDBACKUP_TAB_WGET', true) ?>
			</h4>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_WGET') ?>
				<code>
					wget --max-redirect=10000 "<?= $this->escape($this->checkinfo->frontend->path) ?>" -O - 1>/dev/null 2>/dev/null
				</code>
			</p>

			<h4>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDBACKUP_TAB_CURL', true) ?>
			</h4>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_CURL') ?>
				<code>
					curl -L --max-redirs 1000 -v "<?= $this->escape($this->checkinfo->frontend->path) ?>" 1>/dev/null 2>/dev/null
				</code>
			</p>

			<h4>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDBACKUP_TAB_SCRIPT', true) ?>
			</h4>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_CUSTOMSCRIPT') ?>
			</p>
			<pre style="text-wrap: wrap">
&lt;?php
$curl_handle=curl_init();
curl_setopt($curl_handle, CURLOPT_URL,
  '<?= $this->escape($this->checkinfo->frontend->path) ?>');
curl_setopt($curl_handle,CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($curl_handle,CURLOPT_MAXREDIRS, 10000);
curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, 1);
$buffer = curl_exec($curl_handle);
if (version_compare(PHP_VERSION, '8.5.0', 'lt')) curl_close($curl_handle);
if (empty($buffer))
  echo "Sorry, the backup didn't work.";
else
  echo $buffer;
?&gt;
				</pre>

			<h4>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTENDBACKUP_TAB_URL', true) ?>
			</h4>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_RAWURL') ?>
				<code>
					<?= $this->escape($this->checkinfo->frontend->path) ?>
				</code>
			</p>
		<?php endif ?>
	</div>

</div>
