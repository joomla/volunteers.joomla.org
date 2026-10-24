/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

if (typeof akeebabackup == "undefined")
{
    var akeebabackup = {};
}

if (typeof akeebabackup.S3import == "undefined")
{
    akeebabackup.S3import = {};
}

akeebabackup.S3import.delayedApplicationOfS3KeysInGUI = function ()
{
    var elAccessKey = document.getElementById("s3access");
    var elSecretKey = document.getElementById("s3secret");

    if ((elAccessKey === null) || (elSecretKey === null))
    {
        return;
    }

    /**
     * This makes sure that the S3 access and secret keys are not replaced by the browser or a password manager browser
     * extensions with some arbitrary username and password combination.
     */
    setTimeout(function ()
    {
        document.getElementById("s3access").value = Joomla.getOptions("akeebabackup.S3import.accessKey");
        document.getElementById("s3secret").value = Joomla.getOptions("akeebabackup.S3import.secretKey");
    }, 500);
};

//=============================================================================
// Akeeba Backup Pro - Import arbitrary archives from S3
//=============================================================================

akeebabackup.S3import.resetRoot = function ()
{
    document.getElementById("ak_s3import_folder").value = "";

    return true;
};

akeebabackup.S3import.changeDirectory = function (e)
{
    var elTarget      = e.target;
    var encodedPrefix = elTarget.dataset["s3prefix"] ?? "";

    document.getElementById("ak_s3import_folder").value = atob(encodedPrefix);
    document.forms.adminForm.submit();
};

/**
 *
 * @param {Event} e
 */
akeebabackup.S3import.importFile = function (e)
{
    var elTarget    = e.target;
    var encodedName = elTarget.dataset["s3object"] ?? "";
    var objectName  = atob(encodedName);

    if (objectName === "")
    {
        return false;
    }

    window.location = Joomla.getOptions("akeebabackup.S3import.importURL") + "&file=" + encodeURIComponent(objectName);
};

akeebabackup.System.documentReady(function ()
{
    akeebabackup.S3import.delayedApplicationOfS3KeysInGUI();

    var akeebaS3importResetRoot = document.getElementById('akeebaS3importResetRoot');

    if (akeebaS3importResetRoot)
    {
        akeebaS3importResetRoot.addEventListener("click", akeebabackup.S3import.resetRoot);
    }

    akeebabackup.System.iterateNodes(".akeebaS3importChangeDirectory", function (el)
    {
        el.addEventListener("click", akeebabackup.S3import.changeDirectory);
    });

    akeebabackup.System.iterateNodes(".akeebaS3importObjectDownload", function (el)
    {
        el.addEventListener("click", akeebabackup.S3import.importFile);
    });

    var redirectionURL = Joomla.getOptions("akeebabackup.S3import.autoRedirectURL", "");

    if (redirectionURL !== "")
    {
        window.location = redirectionURL;
    }
});