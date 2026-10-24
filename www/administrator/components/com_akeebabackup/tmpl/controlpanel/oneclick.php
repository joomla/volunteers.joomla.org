<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

?>
<div class="card mb-2">
	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_AKEEBABACKUP_CPANEL_HEADER_QUICKBACKUP') ?>
	</h3>

	<div class="card-body">
		<div class="akeeba-cpanel-container d-flex flex-row flex-wrap align-items-stretch">
			<?php foreach($this->quickIconProfiles as $qiProfile): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-success border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Backup&autostart=1&profileid=<?= (int) $qiProfile->id ?>&<?= Factory::getApplication()->getFormToken() ?>=1">
					<div class="bg-success text-white d-block text-center p-3 h2">
						<span class="fa fa-play"></span>
					</div>
					<span><?= $this->escape($qiProfile->description) ?></span>
				</a>
			<?php endforeach ?>
		</div>
	</div>

</div>
