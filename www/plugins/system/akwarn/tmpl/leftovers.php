<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

/**
 * @var array                                         $leftoverFiles
 * @var \Akeeba\Plugin\System\AKWarn\Extension\AKWarn $this
 */

$l = Factory::getApplication()->getLanguage();
$hasKickstart = array_reduce($leftoverFiles, fn(bool $carry, string $x) => $carry || str_ends_with($x, '.php'), false);
$hasArchives = array_reduce($leftoverFiles, fn(bool $carry, string $x) => $carry || str_ends_with($x, '.jpa') || str_ends_with($x, '.jps') || str_ends_with($x, '.zip'), false);
$actionUri = Uri::getInstance()->toString();

?>
<div class="card border border-warning mb-3">
	<h3 class="card-header bg-warning text-dark">
		<span class="fa fa-fw fa-circle-exclamation me-2" aria-hidden="true"></span>
		<?= $l->_('PLG_SYSTEM_AKWARN_CARD_HEAD') ?>
	</h3>
	<div class="card-body p-3">
		<p class="card-text">
			<?= $l->_('PLG_SYSTEM_AKWARN_INFO') ?>
		</p>
		<p class="card-text">
			<?= $l->_('PLG_SYSTEM_AKWARN_RECOMMEND_DELETE') ?>
		</p>

		<details>
			<summary>
				<?= $l->_('PLG_SYSTEM_AKWARN_DETECTED_FILES') ?>
			</summary>
			<ul>
				<?php foreach($leftoverFiles as $file): ?>
				<li><code><?php echo htmlentities($file, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) ?></code></li>
				<?php endforeach; ?>
			</ul>
		</details>

		<hr>

		<div class="d-flex flex-row flex-wrap align-items-baseline gap-3 justify-content-between"
		     id="plg_system_akwarn_delete">
			<form action="<?= htmlspecialchars($actionUri, ENT_QUOTES, 'UTF-8') ?>" method="post">
				<button type="submit" class="btn btn-primary">
					<span class="fa fa-fw fa-trash-can" aria-hidden="true"></span>
					<?= $l->_('PLG_SYSTEM_AKWARN_DELETE_FILES') ?>
				</button>
				<input type="hidden" name="_akeeba_backup_delete_leftovers" value="1">
				<?= HTMLHelper::_('form.token') ?>
			</form>
		</div>

	</div>
</div>
