<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Backup\Admin\View\ControlPanel\Html */

// Protect from unauthorized access
defined('_JEXEC') || die();

$eid = \Akeeba\Backup\Admin\Helper\Upgrade::getAkeebaBackup8ExtensionId();
$url = sprintf('index.php?option=com_installer&view=manage&filter[search]=id%%3A%d&filter[status]=&filter[client_id]=&filter[type]=&filter[folder]=&filter[core]=', $eid)

?>
<h1>🚨 Please complete your migration to Akeeba Backup 9 🚨</h1>
<p style="font-size: 1.5rem; margin: 1rem 0 0">
	Please click on Components, Akeeba Backup for Joomla!&trade;, Control Panel to open <a href="index.php?option=com_akeebabackup">Akeeba Backup 9</a>'s interface.
</p>
<p style="font-size: 1.5rem; margin: 1rem 0 0">
	Follow the instructions shown in Akeeba Backup 9 to migrate your settings and backups from Akeeba Backup 8 to Akeeba Backup 9.
</p>
<p style="font-size: 1.5rem; margin: 1rem 0 0">
	After you are done migrating please <a href="<?= $url ?>">uninstall Akeeba Backup 8</a>.
</p>
