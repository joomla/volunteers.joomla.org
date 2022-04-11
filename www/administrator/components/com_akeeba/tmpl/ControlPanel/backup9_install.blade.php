<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Backup\Admin\View\ControlPanel\Html */

// Protect from unauthorized access
defined('_JEXEC') || die();

if (version_compare(JVERSION, '4.1.0', 'ge'))
{
	$css = <<< CSS
#akeebaBackup8Wrapper {
    display: none;
}

CSS;

	$js = <<< JS
function akeebaBackup8RegisterWrapperReveal(e) {
    var elDiv = document.getElementById('akeebaBackup8Wrapper');
    var elUpgradeWrapper = document.getElementById('akeebaBackup8UpgradeWrapper');
    var elToggleWrapper = document.getElementById('akeebaBackup8WrapperToggleWrapper');
    var elUnsafeWrapper = document.getElementById('akeebaBackup8UnsafeWrapper');
    if (elDiv) {
        elDiv.style.display = 'block';
    }
    if (elUnsafeWrapper) {
        elUnsafeWrapper.style.display = 'block';
    }
    if (elUpgradeWrapper) {
        elUpgradeWrapper.style.display = 'none';
    }
    if (elToggleWrapper) {
        elToggleWrapper.style.display = 'none';
    }
}

document.addEventListener("DOMContentLoaded", function () {
    var elLink = document.getElementById('akeebaBackup8WrapperToggle');
    if (!elLink) return;
    elLink.addEventListener('click', akeebaBackup8RegisterWrapperReveal);
}, false);

var akeebaBackup8RegisterWrapperRevealTimer = setInterval(function () {
            if(document.readyState !== "complete") return;
            clearInterval(akeebaBackup8RegisterWrapperRevealTimer);
         }, 300);

JS;


	$this->addCssInline($css);
    $this->addJavascriptInline($js);
}

?>

<div class="akeeba-block--warning--large" id="akeebaBackup8UpgradeWrapper">
    <h1>🚨 🚨 🚨 Please upgrade to Akeeeba Backup 9 🚨 🚨 🚨</h1>
    @if (version_compare(JVERSION, '4.1.0', 'ge'))
        <p style="font-size: 1.5rem; margin: 1rem 0 0">
            You are currently using Akeeba Backup 8. This version is only meant to allow you to upgrade from Joomla 3 to Joomla 4 without losing your backup archives and settings. <strong>It is not supported for taking on restoring backups with Joomla {{ JVERSION }}</strong>.
        </p>
    @else
        <p style="font-size: 1.5rem; margin: 1rem 0 0">
            You are currently using Akeeba Backup 8. This version was only made minimally compatible with Joomla 4 to allow you to upgrade from Joomla 3 to Joomla 4 without losing your backup archives and settings. We will not provide support for Akeeba Backup 8 running on Joomla 4.0 and later.
        </p>
    @endif
    <p style="font-size: 1.5rem; margin: 1rem 0 0">
        You need to <a href="https://www.akeeba.com/download.html" target="_blank">download</a> and install Akeeba Backup 9, our Joomla 4 native version of Akeeba Backup. It is fully supported for use on Joomla 4.
    </p>
    <p style="font-size: 1.5rem; margin: 1rem 0 0">
        After installing Akeeba Backup 9 please click on Components, Akeeba Backup <small>for Joomla!&trade;</small>, Control Panel from Joomla's sidebar and follow the instructions on your screen to migrate your settings and your backups from Akeeba Backup 8.
    </p>
    <p>
        <a href="https://www.akeeba.com/download.html" class="akeeba-btn--green--big--block" style="font-size: 1.5rem; margin: 2rem 0">
            <span class="akion akion-ios-download" aria-hidden="true"></span>
            Download Akeeba Backup 9 now
        </a>
    </p>
</div>

@if (version_compare(JVERSION, '4.1.0', 'ge'))
<p class="small" id="akeebaBackup8WrapperToggleWrapper">
    <a href="#" id="akeebaBackup8WrapperToggle">
        I would like to use Akeeba Backup 8 <em>at my own risk</em>
    </a>
    <br/>
    <span style="font-size: smaller">
    By clicking this link you agree that you are doing something we explicitly told you NOT to do, you are fully responsible for your actions and their consequences, the interface or functionality might be broken, you might lose data or damage your site, and that you are not eligible for any support whatsoever.
    </span>
</p>

<div class="akeeba-block--failure--large" id="akeebaBackup8UnsafeWrapper" style="display: none">
    <h3>You are using Akeeba Backup in an UNSUPPORTED environment AT YOUR OWN RISK</h3>
    <p>
        Akeeba Backup 8 is NOT supported for use on Joomla! <?= JVERSION ?>.
    </p>
    <p>
        You have chosen to use it anyway, AT YOUR OWN RISK. By doing this you understand that:
    </p>
    <ul>
        <li>you are doing something we explicitly told you <u>NOT</u> to do.</li>
        <li>you are fully responsible for your actions <strong>and</strong> their consequences.</li>
        <li>the interface or the functionality of the component may be not work fully, correctly or at all.</li>
        <li>you might lose data or otherwise damage your site.</li>
        <li>you are not eligible for <em>ANY SUPPORT WHATSOEVER</em>.</li>
    </ul>
    <p>
        We <strong>VERY STRONGLY</strong> advise you to upgrade to the latest version of Akeeba Backup 9 or later.
    </p>
    <p>
        Consider yourself warned.
    </p>
</div>
@endif
