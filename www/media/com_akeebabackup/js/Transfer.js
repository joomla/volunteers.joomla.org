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

if (typeof akeebabackup.Transfer === "undefined")
{
    akeebabackup.Transfer = {
        lastUrl:      "",
        lastResult:   "",
        FtpTest:      {},
        SftpTest:     {},
        FtpModal:     null,
        URLs:         {},
        translations: {}
    }
}

/**
 * Check the URL field
 */
akeebabackup.Transfer.onUrlChange = function (force)
{
    if (force === undefined)
    {
        force = false;
    }

    var urlBox = document.getElementById("akeeba-transfer-url");
    var url    = urlBox.value;

    if (url == "")
    {
        document.getElementById("akeeba-transfer-lbl-url").style.display = "block";
    }

    if ((url.substring(0, 7) !== "http://") && (url.substring(0, 8) !== "https://"))
    {
        url = "http://" + url;
    }

    var lastUrl    = akeebabackup.Transfer.lastUrl ? akeebabackup.Transfer.lastUrl : Joomla.getOptions(
        "akeebabackup.Transfer.lastUrl", "");
    var lastResult = akeebabackup.Transfer.lastResult ? akeebabackup.Transfer.lastResult : Joomla.getOptions(
        "akeebabackup.Transfer.lastResult", "");

    if (!force && (url === lastUrl))
    {
        akeebabackup.Transfer.applyUrlCheck({
            "status": lastResult,
            "url":    lastUrl
        });

        return;
    }

    var divList = document.querySelectorAll("#akeeba-transfer-row-url > div");

    for (var i = 0; i < divList.length; i++)
    {
        divList[i].style.display = "none";
    }

    urlBox.setAttribute("disabled", "disabled");
    document.getElementById("akeeba-transfer-btn-url").setAttribute("disabled", "disabled");
    document.getElementById("akeeba-transfer-loading").style.display = "";

    akeebabackup.System.doAjax({
            "task": "checkUrl",
            "url":  url
        },
        akeebabackup.Transfer.applyUrlCheck,
        function (msg)
        {
            urlBox.removeAttribute("disabled");
            document.getElementById("akeeba-transfer-btn-url").removeAttribute("disabled");
            document.getElementById("akeeba-transfer-loading").style.display = "none";
        }, false, 10000
    );

    return false;
};

akeebabackup.Transfer.applyUrlCheck = function (response)
{
    var urlBox = document.getElementById("akeeba-transfer-url");

    urlBox.removeAttribute("disabled");
    document.getElementById("akeeba-transfer-btn-url").removeAttribute("disabled");
    document.getElementById("akeeba-transfer-loading").style.display       = "none";
    document.getElementById("akeeba-transfer-ftp-container").style.display = "none";

    urlBox.value = response.url;

    akeebabackup.Transfer.lastResult = response.status;
    akeebabackup.Transfer.lastUrl    = response.url;

    switch (response.status)
    {
        case "ok":
            akeebabackup.Transfer.showConnectionDetails();
            break;

        case "same":
            document.getElementById("akeeba-transfer-err-url-same").style.display = "";
            break;

        case "invalid":
            document.getElementById("akeeba-transfer-err-url-invalid").style.display = "";
            break;

        case "notexists":
            document.getElementById("akeeba-transfer-err-url-notexists").style.display = "";
            break;
    }
};

akeebabackup.Transfer.showConnectionDetails = function ()
{
    document.getElementById("akeeba-transfer-url").setAttribute("disabled", "disabled");
    document.getElementById("akeeba-transfer-btn-url").setAttribute("disabled", "disabled");

    document.getElementById("akeeba-transfer-err-url-notexists").style.display = "none";
    document.getElementById("akeeba-transfer-ftp-container").style.display     = "";
    akeebabackup.Transfer.onTransferMethodChange();

    return false;
};

akeebabackup.Transfer.onTransferMethodChange = function (e)
{
    var elFtpMethod = document.getElementById("akeeba-transfer-ftp-method");
    var method      = elFtpMethod.options[elFtpMethod.selectedIndex].value;

    document.getElementById("akeeba-transfer-ftp-host").parentNode.parentNode.style.display       = "none";
    document.getElementById("akeeba-transfer-ftp-port").parentNode.parentNode.style.display       = "none";
    document.getElementById("akeeba-transfer-ftp-username").parentNode.parentNode.style.display   = "none";
    document.getElementById("akeeba-transfer-ftp-password").parentNode.parentNode.style.display   = "none";
    document.getElementById("akeeba-transfer-ftp-pubkey").parentNode.parentNode.style.display     = "none";
    document.getElementById("akeeba-transfer-ftp-privatekey").parentNode.parentNode.style.display = "none";
    document.getElementById("akeeba-transfer-ftp-directory").parentNode.parentNode.style.display  = "none";
    document.getElementById("akeeba-transfer-ftp-passive-container").style.display                = "none";
    document.getElementById("akeeba-transfer-ftp-passive-fix-container").style.display            = "none";
    document.getElementById("akeeba-transfer-chunkmode").parentNode.parentNode.style.display      = "none";
    document.getElementById("akeeba-transfer-chunksize").parentNode.parentNode.style.display      = "none";
    document.getElementById("akeeba-transfer-apply-loading").style.display                        = "none";

    if (method !== "manual")
    {
        document.getElementById("akeeba-transfer-ftp-host").parentNode.parentNode.style.display      =
            "";
        document.getElementById("akeeba-transfer-ftp-port").parentNode.parentNode.style.display      =
            "";
        document.getElementById("akeeba-transfer-ftp-username").parentNode.parentNode.style.display  =
            "";
        document.getElementById("akeeba-transfer-ftp-password").parentNode.parentNode.style.display  =
            "";
        document.getElementById("akeeba-transfer-ftp-directory").parentNode.parentNode.style.display =
            "";
        document.getElementById("akeeba-transfer-chunkmode").parentNode.parentNode.style.display     = "";
        document.getElementById("akeeba-transfer-chunksize").parentNode.parentNode.style.display     = "";
        document.getElementById("akeeba-transfer-btn-apply").parentNode.parentNode.style.display     = "";
        document.getElementById("akeeba-transfer-manualtransfer").style.display                      = "none";
    }

    if (method === "manual")
    {
        document.getElementById("akeeba-transfer-btn-apply").parentNode.parentNode.style.display = "none";
        document.getElementById("akeeba-transfer-manualtransfer").style.display                  = "";

        return;
    }

    if ((method === "ftp") || (method === "ftps") || (method === "ftpcurl") || (method === "ftpscurl"))
    {
        document.getElementById("akeeba-transfer-ftp-passive-container").style.display = "";
    }

    if ((method === "ftpcurl") || (method === "ftpscurl"))
    {
        document.getElementById("akeeba-transfer-ftp-passive-fix-container").style.display = "";
    }

    if ((method === "sftp") || (method === "sftpcurl"))
    {
        document.getElementById("akeeba-transfer-ftp-pubkey").parentNode.parentNode.style.display     = "";
        document.getElementById("akeeba-transfer-ftp-privatekey").parentNode.parentNode.style.display = "";
    }

};

akeebabackup.Transfer.applyConnection = function ()
{
    document.getElementById("akeeba-transfer-ftp-error").style.display     = "none";
    document.getElementById("akeeba-transfer-apply-loading").style.display = "";

    var button = document.getElementById("akeeba-transfer-btn-apply");
    button.setAttribute("disabled", "disabled");

    document.getElementById("akeeba-transfer-ftp-method").setAttribute("disabled", "disabled");
    document.getElementById("akeeba-transfer-ftp-host").parentNode.parentNode.style.display       = "none";
    document.getElementById("akeeba-transfer-ftp-port").parentNode.parentNode.style.display       = "none";
    document.getElementById("akeeba-transfer-ftp-username").parentNode.parentNode.style.display   = "none";
    document.getElementById("akeeba-transfer-ftp-password").parentNode.parentNode.style.display   = "none";
    document.getElementById("akeeba-transfer-ftp-pubkey").parentNode.parentNode.style.display     = "none";
    document.getElementById("akeeba-transfer-ftp-privatekey").parentNode.parentNode.style.display = "none";
    document.getElementById("akeeba-transfer-ftp-directory").parentNode.parentNode.style.display  = "none";
    document.getElementById("akeeba-transfer-ftp-passive-container").style.display                = "none";
    document.getElementById("akeeba-transfer-ftp-passive-fix-container").style.display            = "none";
    document.getElementById("akeeba-transfer-chunkmode").parentNode.parentNode.style.display      = "none";
    document.getElementById("akeeba-transfer-chunksize").parentNode.parentNode.style.display      = "none";

    var elFtpMethod = document.getElementById("akeeba-transfer-ftp-method");
    var method      = elFtpMethod.options[elFtpMethod.selectedIndex].value;

    if (method === "manual")
    {
        document.getElementById("akeeba-transfer-btn-apply").parentNode.parentNode.style.display = "none";
        document.getElementById("akeeba-transfer-manualtransfer").style.display                  = "";

        return;
    }

    var data = {
        "task":        "applyConnection",
        "method":      method,
        "host":        document.getElementById("akeeba-transfer-ftp-host").value,
        "port":        document.getElementById("akeeba-transfer-ftp-port").value,
        "username":    document.getElementById("akeeba-transfer-ftp-username").value,
        "password":    document.getElementById("akeeba-transfer-ftp-password").value,
        "directory":   document.getElementById("akeeba-transfer-ftp-directory").value,
        "passive":     document.getElementById("akeeba-transfer-ftp-passive1").checked ? 1 : 0,
        "passive_fix": document.getElementById("akeeba-transfer-ftp-passive-fix1").checked ? 1 : 0,
        "privateKey":  document.getElementById("akeeba-transfer-ftp-privatekey").value,
        "publicKey":   document.getElementById("akeeba-transfer-ftp-pubkey").value,
        "chunkMode":   document.getElementById("akeeba-transfer-chunkmode").value,
        "chunkSize":   document.getElementById("akeeba-transfer-chunksize").value
    };

    // Construct the query
    akeebabackup.System.doAjax(
        data,
        function (res)
        {
            document.getElementById("akeeba-transfer-apply-loading").style.display = "none";

            if (!res.status)
            {
                document.getElementById("akeeba-transfer-btn-apply").removeAttribute("disabled");
                document.getElementById("akeeba-transfer-ftp-method").removeAttribute("disabled");

                var akeebaTransferFTPError     = document.getElementById("akeeba-transfer-ftp-error");
                var akeebaTransferFTPErrorBody = document.getElementById("akeeba-transfer-ftp-error-body");
                var akeebaForceButton          = document.getElementById("akeeba-transfer-ftp-error-force");

                if (akeebaForceButton)
                {
                    akeebaForceButton.style.display = "none";

                    if (res.ignorable)
                    {
                        akeebaForceButton.style.display = "";
                    }
                }

                akeebaTransferFTPErrorBody.innerHTML = res.message;
                akeebaTransferFTPError.style.display = "";
                akeebabackup.System.triggerEvent(akeebaTransferFTPError, "focus");

                akeebabackup.Transfer.onTransferMethodChange();

                return;
            }

            // Successful connection; perform preliminary checks and upload Kickstart
            akeebabackup.Transfer.uploadKickstart();

        },
        function (res)
        {
            document.getElementById("akeeba-transfer-apply-loading").style.display = "none";

            document.getElementById("akeeba-transfer-btn-apply").removeAttribute("disabled");
            document.getElementById("akeeba-transfer-ftp-method").removeAttribute("disabled");

            var elFtpError             = document.getElementById("akeeba-transfer-ftp-error");
            var elFtpErrorBody         = document.getElementById("akeeba-transfer-ftp-error-body");
            elFtpErrorBody.textContent = Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_FAIL");
            elFtpError.style.display   = "";
            akeebabackup.System.triggerEvent(elFtpError, "focus");

            akeebabackup.Transfer.onTransferMethodChange();
        }
        , false, 15000
    );
};

akeebabackup.Transfer.uploadKickstart = function ()
{
    var stepKickstart   = document.getElementById("akeeba-transfer-upload-lbl-kickstart");
    var stepArchive     = document.getElementById("akeeba-transfer-upload-lbl-archive");
    var uploadErrorBox  = document.getElementById("akeeba-transfer-upload-error");
    var uploadErrorBody = document.getElementById("akeeba-transfer-upload-error-body");
    var uploadForce     = document.getElementById("akeeba-transfer-upload-error-force");

    uploadErrorBox.style.display = "none";
    uploadForce.style.display    = "none";
    uploadErrorBody.innerHTML    = "";

    stepKickstart.classList.remove('bg-light');
    stepKickstart.classList.add('bg-warning');

    stepArchive.classList.remove('bg-warning');
    stepArchive.classList.add('bg-light');

    document.getElementById("akeeba-transfer-upload-area-kickstart").style.display = "none";
    document.getElementById("akeeba-transfer-upload-area-upload").style.display    = "";
    document.getElementById("akeeba-transfer-upload").style.display                = "";

    var data = {
        "task": "initialiseUpload"
    };

    // Construct the query
    akeebabackup.System.doAjax(data, function (res)
    {
        if (!res.status)
        {
            if (uploadForce)
            {
                uploadForce.style.display = "none";

                if (res.ignorable)
                {
                    uploadForce.style.display = "";
                }
            }

            stepKickstart.classList.remove('bg-warning');
            stepKickstart.classList.add('bg-danger', 'text-white');

            uploadErrorBody.innerHTML    = res.message;
            uploadErrorBox.style.display = "";

            return;
        }

        // Success. Now let's upload the backup archive.
        akeebabackup.Transfer.uploadArchive(1);
    }, null, false, 150000);
};

akeebabackup.Transfer.uploadArchive = function (start)
{
    if (start === undefined)
    {
        start = 0;
    }

    var stepKickstart   = document.getElementById("akeeba-transfer-upload-lbl-kickstart");
    var stepArchive     = document.getElementById("akeeba-transfer-upload-lbl-archive");
    var uploadErrorBox  = document.getElementById("akeeba-transfer-upload-error");
    var uploadErrorBody = document.getElementById("akeeba-transfer-upload-error-body");

    uploadErrorBody.innerHTML    = "";
    uploadErrorBox.style.display = "none";

    stepKickstart.classList.remove('bg-light', 'bg-warning');
    stepKickstart.classList.add('bg-success', 'text-white');

    stepArchive.classList.remove('bg-light');
    stepArchive.classList.add('bg-warning');

    var data = {
        "task": "upload", "start": start
    };

    // Construct the query
    akeebabackup.System.doAjax(data, function (res)
    {
        if (!res.result)
        {
            stepArchive.classList.remove('bg-warning');
            stepArchive.classList.add('bg-danger', 'text-white');

            uploadErrorBody.innerHTML    = res.message;
            uploadErrorBox.style.display = "";

            return;
        }

        // Success. Let's update the displayed information and step through the upload.
        if (res.done)
        {
            document.getElementById("akeeba-transfer-upload-percent").textContent = "100 %";
            document.getElementById("akeeba-transfer-upload-size").innerHTML      = "";

            document.getElementById("akeeba-transfer-upload-area-kickstart").style.display = "";
            document.getElementById("akeeba-transfer-upload-area-upload").style.display    = "none";

            const kickstartName = Joomla.getOptions("akeebabackup.transfer")?.randomName ?? "kickstart.php";
            const urlBox        = document.getElementById("akeeba-transfer-url");
            const url           = urlBox.value.replace(/\/$/, "") + "/" + kickstartName;

            document.getElementById("akeeba-transfer-upload-btn-kickstart").setAttribute("href", url);

            return;
        }

        var donePercent = 0;
        var totalSize   = res.totalSize * 1.0;
        var doneSize    = res.doneSize * 1.0;

        if ((totalSize > 0) && (doneSize > 0))
        {
            donePercent = 100 * (doneSize / totalSize);
        }

        document.getElementById("akeeba-transfer-upload-percent").textContent = donePercent.toFixed(2) + "%";
        document.getElementById("akeeba-transfer-upload-size").textContent    = doneSize.toFixed(
            0) + " / " + totalSize.toFixed(0) + " bytes";

        // Using setTimeout prevents recursive call issues.
        window.setTimeout(function ()
        {
            akeebabackup.Transfer.uploadArchive(0);
        }, 50);
    }, null, false, 150000);
};

akeebabackup.Transfer.testFtpSftpConnection = function ()
{
    var elFtpMethod = document.getElementById("akeeba-transfer-ftp-method");
    var driver      = elFtpMethod.options[elFtpMethod.selectedIndex].value;

    if ((driver === "ftp") || (driver === "ftps") || (driver === "ftpcurl") || (driver === "ftpscurl"))
    {
        akeebabackup.Transfer.FtpTest.testConnection("akeeba-transfer-btn-testftp");
    }
    else if ((driver === "sftp") || (driver === "sftpcurl"))
    {
        akeebabackup.Transfer.SftpTest.testConnection("akeeba-transfer-btn-testftp");
    }

    return false;
};

//=============================================================================
// 							I N I T I A L I Z A T I O N
//=============================================================================
akeebabackup.System.documentReady(function ()
{
    document.getElementById("akeeba-transfer-ftp-method")
            .addEventListener("change", akeebabackup.Transfer.onTransferMethodChange);

    document.getElementById("akeeba-transfer-btn-url").addEventListener("click", function (e)
    {
        e.preventDefault();

        akeebabackup.Transfer.onUrlChange(true);

        return false;
    });

    // Auto-process URL change event
    if (document.getElementById("akeeba-transfer-url").value)
    {
        akeebabackup.Transfer.onUrlChange();
    }

    // Remote connection hooks
    document.getElementById("akeeba-transfer-ftp-method")
            .addEventListener("change", akeebabackup.Transfer.onTransferMethodChange);

    document.getElementById("akeeba-transfer-btn-apply")
            .addEventListener("click", akeebabackup.Transfer.applyConnection);

    document.getElementById("akeeba-transfer-err-url-notexists-btn-ignore")
            .addEventListener("click", akeebabackup.Transfer.showConnectionDetails);
});