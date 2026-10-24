<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

?>
<div class="akeebabackup-cpanel-footer mt-3 p-3 bg-light border-top border-4 d-flex flex-column">
	<p class="text-muted">
		Copyright 2006-<?= date('Y') ?> <a href="https://www.akeeba.com">Akeeba Ltd</a>. All legal rights reserved.
		<br/>
		Akeeba Backup is Free Software and is distributed under the terms of the
		<a href="http://www.gnu.org/licenses/gpl-3.0.html">GNU General Public License</a>, version 3 or –
		at your option - any later version.
	</p>

	<?php if(AKEEBABACKUP_PRO != 1): ?>
	<p>
			If you use Akeeba Backup Core, please post a rating and a review at the
			<a href="https://extensions.joomla.org/extensions/extension/access-a-security/site-security/akeeba-backup/">Joomla! Extensions Directory</a>.
	</p>
	<?php endif; ?>
</div>
