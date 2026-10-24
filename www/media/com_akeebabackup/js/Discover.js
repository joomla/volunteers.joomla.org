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

akeebabackup.System.documentReady(function ()
{
    document.getElementById("browsebutton")
            .addEventListener("click", function ()
            {
                var directory = document.getElementById("directory");

                akeebabackup.Configuration.onBrowser(directory.value, directory);
            });
});