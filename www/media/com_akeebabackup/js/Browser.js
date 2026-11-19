/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

if (typeof akeebabackup === "undefined")
{
    var akeebabackup = {};
}

if (typeof akeebabackup.Browser == "undefined")
{
    akeebabackup.Browser = {
        useThis: function () {
            var rawFolder = document.forms.adminForm.folderraw.value;

            // Ignore any trailing directory separator, e.g. "[SITEROOT]/"
            if (rawFolder.replace(/[\/\\]+$/, "") === "[SITEROOT]")
            {
                rawFolder = "[SITETMP]";

                alert(Joomla.Text._("COM_AKEEBABACKUP_CONFIG_UI_ROOTDIR"));
            }

            window.parent.akeebabackup.Configuration.onBrowserCallback(rawFolder);
        }
    };
}

akeebabackup.System.addEventListener('comAkeebaBrowserUseThis', 'click', function () {
    akeebabackup.Browser.useThis();

    return false;
});
akeebabackup.System.addEventListener('comAkeebaBrowserGo', 'click', function () {
    document.forms.adminForm.submit();

    return false;
});
