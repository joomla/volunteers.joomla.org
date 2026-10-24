<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Alice\HtmlView $this */
?>

<div class="akeeba-panel--red">
    <header class="akeeba-block-header">
        <h3>
            <?= Text::_('COM_AKEEBABACKUP_ALICE_ERR_ANALYZEFAILED_HEADER') ?>
        </h3>
    </header>
    <p>
        <?= Text::_('COM_AKEEBABACKUP_ALICE_ERR_ANALYZEFAILED_INFO') ?>
    </p>
    <h4>
            <span class="akeeba-label--red--small">
                <?= $this->errorException->getCode() ?>
            </span>
        <?= $this->errorException->getMessage() ?>
    </h4>
    <p>
        <?= $this->errorException->getFile() ?> :: L<?= $this->errorException->getLine() ?>
    </p>
    <pre><?= $this->errorException->getTraceAsString() ?></pre>
</div>
