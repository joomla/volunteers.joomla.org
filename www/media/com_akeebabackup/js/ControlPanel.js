/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.akeebabackup = window.akeebabackup || {};
window.akeebabackup.ControlPanel = window.akeebabackup.ControlPanel || {
    needsDownloadID:        true,
    outputDirUnderSiteRoot: false,
    hasSecurityFiles:       false,

    checkOutputFolderSecurity: function () {
        if (!Joomla.getOptions("akeebabackup.ControlPanel.outputDirUnderSiteRoot", false)) {
            return;
        }

        akeebabackup.System.doAjax({
                ajaxURL: "index.php?option=com_akeebabackup&view=Controlpanel&task=checkOutputDirectory&format=raw"
            }, function (data) {
                var readFile   = data.hasOwnProperty("readFile") ? data.readFile : false;
                var listFolder = data.hasOwnProperty("listFolder") ? data.listFolder : false;
                var isSystem   = data.hasOwnProperty("isSystem") ? data.isSystem : false;
                var hasRandom  = data.hasOwnProperty("hasRandom") ? data.hasRandom : true;

                if (listFolder && isSystem)
                {
                    document.getElementById("outDirSystem").style.display = "block";
                }
                else if (listFolder)
                {
                    document.getElementById("insecureOutputDirectory").style.display = "block";
                }
                else if (readFile && !listFolder && !hasRandom)
                {
                    if (!akeeba.System.getOptions("akeeba.ControlPanel.hasSecurityFiles", true))
                    {
                        document.getElementById("insecureOutputDirectory").style.display = "block";

                        return;
                    }

                    if (!hasRandom)
                    {
                        document.getElementById("missingRandomFromFilename").style.display = "block";
                    }
                }
            }, function (message) {
                // I can ignore errors for this AJAX requesy
            }, false
        );
    }
};

window.addEventListener('DOMContentLoaded', function(event) {
    akeebabackup.System.addEventListener("comAkeebaControlPanelProfileSwitch", "choice", function ()
    {
        // The timeout is necessary. The choice event is fired before the hidden SELECT element is updated. There is no
        // event after that change takes place. Therefore we need to wait a little bit for the change to take effect.
        setTimeout(function () {
            document.forms.switchActiveProfileForm.submit();
        }, 500);
    });

    akeebabackup.System.notification.askPermission();
    akeebabackup.ControlPanel.checkOutputFolderSecurity();

    var elNotFixedPerms = document.getElementById("notfixedperms");

    if (elNotFixedPerms !== null)
    {
        elNotFixedPerms.parentElement.removeChild(elNotFixedPerms);
    }
});