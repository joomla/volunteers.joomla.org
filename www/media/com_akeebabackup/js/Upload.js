/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

// Object initialisation
if (typeof akeebabackup === "undefined")
{
    var akeebabackup = {};
}

if (typeof akeebabackup.Upload === "undefined")
{
    akeebabackup.Upload = {}
}

akeebabackup.Upload.autoSubmit = function ()
{
    var akeebaform = document.forms["akeebauploadform"];

    if (!akeebaform)
    {
        return;
    }

    akeebaform.submit();
};

akeebabackup.Upload.autoClose = function ()
{
    var elMessage = document.getElementById("comAkeebaUploadDone");

    // Only applies on the "done" layout
    if (elMessage === null)
    {
        return;
    }

    window.setTimeout(function ()
    {
        parent.akeebabackup.Manage.uploadModal.hide();
    }, 3000);
};

akeebabackup.System.documentReady(function ()
{
    // Auto-submit the form "akeebaform" in the default and uploading layouts
    akeebabackup.Upload.autoSubmit();

    // Auto-close the window in the "done" layout
    akeebabackup.Upload.autoClose();
});