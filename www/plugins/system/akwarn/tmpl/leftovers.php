<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * @var array                                         $leftoverFiles
 * @var \Akeeba\Plugin\System\AKWarn\Extension\AKWarn $this
 */

$l = Factory::getApplication()->getLanguage();
$hasKickstart = array_reduce($leftoverFiles, fn(bool $carry, string $x) => $carry || str_ends_with($x, '.php'), false);
$hasArchives = array_reduce($leftoverFiles, fn(bool $carry, string $x) => $carry || str_ends_with($x, '.jpa') || str_ends_with($x, '.jps') || str_ends_with($x, '.zip'), false);
$actionUri = clone Uri::getInstance();
$actionUri->setVar('_akeeba_backup_delete_leftovers', $this->getApplication()->getSession()->getToken());

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
				<li><code><?php echo htmlentities($file) ?></code></li>
				<?php endforeach; ?>
			</ul>
		</details>

		<hr>

		<div class="d-flex flex-row flex-wrap align-items-baseline gap-3 justify-content-between"
		     id="plg_system_akwarn_delete">
			<a href="<?= $actionUri->toString() ?>" class="btn btn-primary">
				<span class="fa fa-fw fa-trash-can" aria-hidden="true"></span>
				<?= $l->_('PLG_SYSTEM_AKWARN_DELETE_FILES') ?>
			</a>
		</div>

	</div>
</div>
