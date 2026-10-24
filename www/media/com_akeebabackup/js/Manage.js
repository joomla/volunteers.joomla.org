/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

if (typeof (akeebabackup) == "undefined")
{
    var akeebabackup = {};
}

if (typeof akeebabackup.Manage == "undefined")
{
    akeebabackup.Manage = {
        remoteManagementModal: null,
        uploadModal:           null,
        downloadModal:         null,
        infoModal:             null
    }
}

akeebabackup.Manage.onRemoteManagementClick = function (managementUrl, reloadUrl)
{
    akeebabackup.Manage.remoteManagementModal = new bootstrap.Modal(
        document.getElementById("akeebabackup-manage-iframe-modal"),
        {
            keyboard:   false,
            background: "static"
        }
    );

    var elModal = document.getElementById('akeebabackup-manage-iframe-modal');

    elModal.addEventListener("show.bs.modal", function (event)
    {
        var elTitle   = document.getElementById("akeebabackup-manage-iframe-modal-title");
        var elClose   = document.getElementById("akeebabackup-manage-iframe-modal-close");
        var elContent = document.getElementById("akeebabackup-manage-iframe-modal-content")

        elTitle.innerHTML = Joomla.Text._("COM_AKEEBABACKUP_BUADMIN_LABEL_REMOTEFILEMGMT");
        elClose.classList.remove("d-none");
        elContent.innerHTML = "";

        var elIframe    = document.createElement("iframe");
        elIframe.src    = managementUrl;
        elIframe.width  = "100%";
        elIframe.height = 400;

        elContent.appendChild(elIframe);
    });

    elModal.addEventListener("hidden.bs.modal", function (event)
    {
        window.location = reloadUrl;
    });

    akeebabackup.Manage.remoteManagementModal.show();
};

akeebabackup.Manage.onUploadClick = function (uploadURL, reloadUrl)
{
    akeebabackup.Manage.uploadModal = new bootstrap.Modal(
        document.getElementById("akeebabackup-manage-iframe-modal"),
        {
            keyboard:   false,
            background: "static"
        }
    );

    var elModal = document.getElementById('akeebabackup-manage-iframe-modal');

    elModal.addEventListener("show.bs.modal", function (event)
    {
        var elTitle   = document.getElementById("akeebabackup-manage-iframe-modal-title");
        var elClose   = document.getElementById("akeebabackup-manage-iframe-modal-close");
        var elContent = document.getElementById("akeebabackup-manage-iframe-modal-content")

        elTitle.innerHTML = Joomla.Text._("COM_AKEEBABACKUP_REMOTEFILES_INPROGRESS_HEADER");
        elClose.classList.add("d-none");
        elContent.innerHTML = "";

        var elIframe    = document.createElement("iframe");
        elIframe.src    = uploadURL;
        elIframe.width  = "100%";
        elIframe.height = 300;

        elContent.appendChild(elIframe);
    });

    elModal.addEventListener("hidden.bs.modal", function (event)
    {
        window.location = reloadUrl;
    });

    akeebabackup.Manage.uploadModal.show();
};

akeebabackup.Manage.confirmDownload = function (e)
{
    // var answer = confirm(Joomla.Text._("COM_AKEEBABACKUP_BUADMIN_LOG_DOWNLOAD_CONFIRM"));
    //
    // if (!answer)
    // {
    //     return;
    // }

    var clickedElement = e.target;

    if (clickedElement === null)
    {
        return;
    }

    var id   = clickedElement.dataset['id'] ?? 'id1';
    var part = clickedElement.dataset['part'] ?? '';

    if (id < 0)
    {
        return;
    }

    var newURL = Joomla.getOptions("akeebabackup.Manage.downloadURL") +
        "&id=" + id;

    if ((typeof part === "undefined") || (part !== ""))
    {
        newURL += "&part=" + part
    }

    console.log('Opening URL ' + newURL);

    window.location = newURL;
};

akeebabackup.System.documentReady(function ()
{
    // Add click event handlers to download buttons

    akeebabackup.System.iterateNodes(".comAkeebaManageDownloadButton", function (el)
    {
        akeebabackup.System.addEventListener(el, "click", akeebabackup.Manage.confirmDownload);
    });

    akeebabackup.System.iterateNodes(".akeeba_remote_management_link", function (el)
    {
        akeebabackup.System.addEventListener(el, "click", function (e)
        {
            e.preventDefault();


            var managementUrl = el.dataset['management'] ?? '';
            var reloadUrl     = el.dataset['reload'] ?? '';

            if ((managementUrl === "") || (reloadUrl === ""))
            {
                return false;
            }

            akeebabackup.Manage.onRemoteManagementClick(managementUrl, reloadUrl);

            return false;
        });
    });

    akeebabackup.System.iterateNodes(".akeeba_upload", function (el)
    {
        akeebabackup.System.addEventListener(el, "click", function (e)
        {
            e.preventDefault();

            var uploadUrl = el.dataset['upload'] ?? '';
            var reloadUrl = el.dataset['reload'] ?? '';

            if ((uploadUrl === "") || (reloadUrl === ""))
            {
                return false;
            }

            akeebabackup.Manage.onUploadClick(uploadUrl, reloadUrl);

            return false;
        });
    });

    // Show the how to restore modal if necessary
    if (Joomla.getOptions("akeebabackup.Manage.ShowHowToRestoreModal", 0))
    {
        setTimeout(function ()
        {
            akeebabackup.System.howToRestoreModal = new bootstrap.Modal(
                document.getElementById("akeebabackup-config-howtorestore-bubble"), {
                    backdrop: "static"
                });
            akeebabackup.System.howToRestoreModal.show();
        }, 500);
    }
});