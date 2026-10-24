<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Schedule\HtmlView $this */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Engine\Platform;
use Joomla\CMS\Language\Text;

?>
<div class="card mb-3">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPIBACKUP') ?>
	</h3>

	<div class="card-body">
		<div class="alert alert-info">
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPIBACKUP_INFO') ?>
			</p>
		</div>

		<?php if(!$this->croninfo->info->jsonapi): ?>
			<div class="alert alert-danger">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_DISABLED') ?>
				</p>
				<p>
					<a href="<?= $this->enableJsonApiURL ?>" class="btn btn-primary">
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_BTN_ENABLE_JSONAPI') ?>
					</a>
				</p>
			</div>
		<?php elseif(!trim($this->croninfo->info->secret)): ?>
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
			<h4><?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_ARCCLI') ?></h4>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_ARCCLI_INTRO') ?>
			</p>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_ARCCLI_DOCKER') ?>
			</p>
			<p>
				<code><?= sprintf(
						'docker run --rm ghcr.io/akeeba/remotecli backup --profile=%d --host="%s" --secret="%s"',
						Platform::getInstance()->get_active_profile(),
						$this->escape($this->croninfo->json->path ),
						$this->escape($this->croninfo->info->secret )
					) ?></code>
			</p>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_ARCCLI_PHAR') ?>
			</p>
			<p>
				<code><?= sprintf(
						'php remote.phar backup --profile=%d --host="%s" --secret="%s"',
						Platform::getInstance()->get_active_profile(),
						$this->escape($this->croninfo->json->path ),
						$this->escape($this->croninfo->info->secret )
					) ?></code>
			</p>

			<h4>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_OTHER') ?>
			</h4>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_INTRO') ?>
			</p>

			<table class="table table-striped">
				<tbody>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_ENDPOINT')?>
					</td>
					<td>
						<?= $this->escape($this->croninfo->json->path) ?>
					</td>
				</tr>
				<tr>
					<td>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_SECRET') ?>
					</td>
					<td>
						<?= $this->escape($this->croninfo->info->secret) ?>
					</td>
				</tr>
				</tbody>
			</table>

			<p>
				<small>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JSONAPI_DISCLAIMER') ?>
				</small>
			</p>



		<?php endif ?>
	</div>
</div>