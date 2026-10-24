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

if (typeof akeebabackup.RemoteFiles == "undefined")
{
    akeebabackup.RemoteFiles = {};
}

/**
 * Shows the modal "please wait" card.
 *
 * This informs the user that something is happening in the background instead of letting them wonder if the software is
 * broken. It also prevents impatient users from multiple-clicking an action button which could have unintended
 * consequences; none of the remote files actions are very good candidates for parallel execution.
 */
akeebabackup.RemoteFiles.showWaitModalFirst = function (e)
{
    document.getElementById("akeebaBackupRemoteFilesWorkInProgress").classList.remove('d-none');
    document.getElementById("akeebaBackupRemoteFilesMainInterface").classList.add('d-none');
};

akeebabackup.System.documentReady(function ()
{
    // Action button anchors: show the modal "please wait" card when clicked
    akeebabackup.System.iterateNodes(".akeebaRemoteFilesShowWait", function (el)
    {
        akeebabackup.System.addEventListener(el, "click", akeebabackup.RemoteFiles.showWaitModalFirst);
    });

    // Disabled button anchors: cancel the click event
    akeebabackup.System.iterateNodes(".akeebaBackupRemoteFilesMainInterface[disabled=\"disabled\"]", function (el)
    {
        akeebabackup.System.addEventListener(el, "click", function ()
        {
            return false;
        });
    });

    // dlprogress view: autosubmit form
    var adminForm = document.getElementById("adminForm");

    if (!adminForm)
    {
        return;
    }

    adminForm.submit();
});