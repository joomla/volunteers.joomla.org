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

if (typeof akeebabackup.Restore == "undefined")
{
    akeebabackup.Restore = {
        lastResponseSeconds: 0,
        timer:               0,
        errorCallback:       null,
        statistics:          {
            inbytes:  0,
            outbytes: 0,
            files:    0
        },
        factory:             null
    };

}

/**
 * Callback script for AJAX errors
 * @param msg
 * @return
 */
akeebabackup.Restore.errorCallbackDefault = function (msg)
{
    document.getElementById("restoration-progress").style.display = "none";
    document.getElementById("restoration-error").style.display    = "block";
    document.getElementById("backup-error-message").innerHTML     = msg;
};

/**
 * Performs an AJAX request to the restoration script (restore.php)
 *
 * @param data
 * @param successCallback
 * @param errorCallback
 *
 * @return
 */
akeebabackup.Restore.doAjax = function (data, successCallback, errorCallback)
{
    var json = JSON.stringify(data);

    var post_data = {
        json: json
    };

    // Authentication method for Akeeba Restore 5.4.0 or later: send the password
    var restorationPassword = Joomla.getOptions("akeebabackup.Restore.password", "");

    if (restorationPassword.length > 0)
    {
        post_data.password = restorationPassword;
    }

    // Make the request skip the cache appending the microsecond timestamp an extra, ignored query string parameter.
    var now                     = new Date().getTime() / 1000;
    var s                       = parseInt(now, 10);
    post_data._cacheBustingJunk = Math.round((now - s) * 1000);

    var structure =
            {
                type:    "POST",
                url:     Joomla.getOptions("akeebabackup.Restore.ajaxURL", ""),
                cache:   false,
                data:    post_data,
                timeout: 600000,
                success: function (msg)
                         {
                             // Initialize
                             var junk    = null;
                             var message = "";

                             // Get rid of junk before the data
                             var valid_pos = msg.indexOf("###");

                             if (valid_pos == -1)
                             {
                                 // Valid data not found in the response
                                 msg = "Invalid AJAX data: " + msg;

                                 if (errorCallback == null)
                                 {
                                     if (akeebabackup.Restore.errorCallback != null)
                                     {
                                         akeebabackup.Restore.errorCallback(msg);
                                     }
                                     else
                                     {
                                         akeebabackup.Restore.errorCallbackDefault(msg);
                                     }
                                 }
                                 else
                                 {
                                     errorCallback(msg);
                                 }

                                 return;
                             }
                             else if (valid_pos != 0)
                             {
                                 // Data is prefixed with junk
                                 junk    = msg.substr(0, valid_pos);
                                 message = msg.substr(valid_pos);
                             }
                             else
                             {
                                 message = msg;
                             }

                             message = message.substr(3); // Remove triple hash in the beginning

                             // Get of rid of junk after the data
                             valid_pos = message.lastIndexOf("###");
                             message   = message.substr(0, valid_pos); // Remove triple hash in the end


                             try
                             {
                                 var data = JSON.parse(message);
                             }
                             catch (err)
                             {
                                 var errorMessage = err.message + "\n<br/>\n<pre>\n" + message + "\n</pre>";
                                 if (errorCallback == null)
                                 {
                                     if (akeebabackup.Restore.errorCallback != null)
                                     {
                                         akeebabackup.Restore.errorCallback(errorMessage);
                                     }
                                     else
                                     {
                                         akeebabackup.Restore.errorCallbackDefault(errorMessage);
                                     }
                                 }
                                 else
                                 {
                                     errorCallback(errorMessage);
                                 }
                                 return;
                             }

                             // Call the callback function
                             successCallback(data);
                         },
                error:   function (Request, textStatus, errorThrown)
                         {
                             var text    = Request.responseText ? Request.responseText : "";
                             var message = "<strong>AJAX Loading Error</strong><br/>HTTP Status: " + Request.status + " (" +
                                 Request.statusText + ")<br/>";

                             message = message + "Internal status: " + textStatus + "<br/>";
                             message = message + "XHR ReadyState: " + Request.readyState + "<br/>";
                             message =
                                 message + "Raw server response:<br/>" + akeebabackup.System.sanitizeErrorMessage(text);


                             if (errorCallback == null)
                             {
                                 if (akeebabackup.Restore.errorCallback != null)
                                 {
                                     akeebabackup.Restore.errorCallback(message);
                                 }
                                 else
                                 {
                                     akeebabackup.Restore.errorCallbackDefault(message);
                                 }
                             }
                             else
                             {
                                 errorCallback(message);
                             }
                         }
            };

    akeebabackup.Ajax.ajax(structure);
};

/**
 * Starts the timer for the last response timer
 *
 * @param   max_allowance  Maximum time allowance in seconds
 * @param   bias           Runtime bias in %
 */
akeebabackup.Restore.startTimeoutBar = function (max_allowance, bias)
{
    akeebabackup.Restore.resetTimeoutBar();

    akeebabackup.Restore.timer = setInterval(function ()
    {
        akeebabackup.Restore.lastResponseSeconds++;
        var lastText = Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE")
                             .replace("%s", akeebabackup.Restore.lastResponseSeconds.toFixed(0));

        try
        {
            document.getElementById("response-timer").querySelector("div.text").textContent = lastText;
        }
        catch (e)
        {
        }
    }, 1000);
};

/**
 * Resets the last response timer bar
 */
akeebabackup.Restore.resetTimeoutBar = function ()
{
    akeebabackup.Restore.lastResponseSeconds = 0;

    if (akeebabackup.Restore.timer == 0)
    {
        return;
    }

    clearInterval(akeebabackup.Restore.timer);
    akeebabackup.Restore.timer = 0;

    var timerText = document.getElementById("response-timer").querySelector("div.text");
    var lastText  = Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE").replace("%s", "0");

    try
    {
        timerText.textContent = lastText;
    }
    catch (e)
    {
    }
};

/**
 * Pings the restoration script (making sure its executable!!)
 * @return
 */
akeebabackup.Restore.pingRestoration = function ()
{
    // Reset variables
    akeebabackup.Restore.statistics.inbytes  = 0;
    akeebabackup.Restore.statistics.outbytes = 0;
    akeebabackup.Restore.statistics.files    = 0;

    // Do AJAX post
    var post = {task: "ping"};
    akeebabackup.Restore.startTimeoutBar(5000, 80);
    akeebabackup.Restore.doAjax(post, function (data)
    {
        akeebabackup.Restore.start(data);
    });
};

/**
 * Starts the restoration
 * @return
 */
akeebabackup.Restore.start = function ()
{
    // Reset variables
    akeebabackup.Restore.statistics.inbytes  = 0;
    akeebabackup.Restore.statistics.outbytes = 0;
    akeebabackup.Restore.statistics.files    = 0;

    // Do AJAX post
    var post = {task: "startRestore"};
    akeebabackup.Restore.startTimeoutBar(5000, 80);
    akeebabackup.Restore.doAjax(post, function (data)
    {
        akeebabackup.Restore.step(data);
    });
};

/**
 * Steps through the restoration
 * @param data
 * @return
 */
akeebabackup.Restore.step = function (data)
{
    akeebabackup.Restore.resetTimeoutBar();

    if (data.status == false)
    {
        // handle failure
        akeebabackup.Restore.errorCallbackDefault(data.message);
    }
    else
    {
        if (data.done)
        {
            akeebabackup.Restore.factory                                    = data.factory;
            // handle finish
            document.getElementById("restoration-progress").style.display   = "none";
            document.getElementById("restoration-extract-ok").style.display = "block";
        }
        else
        {
            // Add data to variables
            akeebabackup.Restore.statistics.inbytes += data.bytesIn;
            akeebabackup.Restore.statistics.outbytes += data.bytesOut;
            akeebabackup.Restore.statistics.files += data.files;

            // Display data
            try
            {
                document.getElementById("extbytesin").textContent  = akeebabackup.Restore.statistics.inbytes;
                document.getElementById("extbytesout").textContent = akeebabackup.Restore.statistics.outbytes;
                document.getElementById("extfiles").textContent    = akeebabackup.Restore.statistics.files;
            }
            catch (e)
            {
            }

            // Do AJAX post
            var post = {
                task:    "stepRestore",
                factory: data.factory
            };
            akeebabackup.Restore.startTimeoutBar(5000, 80);
            akeebabackup.Restore.doAjax(post, function (data)
            {
                akeebabackup.Restore.step(data);
            });
        }
    }
};

/**
 * Finalizes the restoration.
 *
 * @param {Event} e
 *
 * @returns {boolean}  Returns false to cancel the button click
 */
akeebabackup.Restore.finalize = function (e)
{
    e.preventDefault();

    // Do AJAX post
    var post = {task: "finalizeRestore", factory: akeebabackup.Restore.factory};

    akeebabackup.Restore.startTimeoutBar(5000, 80);
    akeebabackup.Restore.doAjax(post, function (data)
    {
        akeebabackup.Restore.finished(data);
    });

    return false;
};

akeebabackup.Restore.finished = function ()
{
    // We're just finished - return to the back-end Control Panel
    window.location = Joomla.getOptions("akeebabackup.Restore.mainURL", window.location);
};

/**
 * Opens a new window / tab with the restoration script
 *
 * @param {Event} e
 *
 * @returns {boolean}  Returns false to cancel the button click event
 */
akeebabackup.Restore.runInstaller = function (e)
{
    e.preventDefault();

    window.open("../installation/index.php", "abiinstaller");

    var runInstaller = document.getElementById("restoration-runinstaller");
    var finalize     = document.getElementById("restoration-finalize");

    runInstaller.className = "btn btn-outline-dark btn-sm me-3";
    finalize.style.display = "block";

    return false;
};

akeebabackup.Restore.restoreDefaultOptions = function ()
{
    var jpskey = document.getElementById("jps_key");

    if (jpskey)
    {
        jpskey.value = "ThisIsADummyStringToWorkAroundChrome";
        jpskey.value = "";
    }
};

akeebabackup.Restore.onProcEngineChange = function (e)
{
    var elProcEngine = document.getElementById("procengine");

    if (elProcEngine.options[elProcEngine.selectedIndex].value === "direct")
    {
        document.getElementById("ftpOptions").style.display = "none";
        document.getElementById("testftp").style.display    = "none";
    }
    else
    {
        document.getElementById("ftpOptions").style.display = "block";
        document.getElementById("testftp").style.display    = "inline-block";
    }
};

akeebabackup.System.documentReady(function ()
{
    if (Joomla.getOptions("akeebabackup.Restore.inMainRestoration", false))
    {
        // Actual restoration page – hook the buttons and start extracting the archive

        document.getElementById("restoration-runinstaller")
                .addEventListener("click", akeebabackup.Restore.runInstaller);
        document.getElementById("restoration-finalize")
                .addEventListener("click", akeebabackup.Restore.finalize);

        akeebabackup.Restore.pingRestoration();

        return;
    }

    // Restoration setup page – Hook the buttons and dropdowns
    document.getElementById("backup-start")
            .addEventListener("click", function (e)
            {
                e.preventDefault();

                document.adminForm.submit();

                return false;
            });

    document.getElementById("testftp")
            .addEventListener("click", function (e)
            {
                e.preventDefault();

                akeebabackup.Configuration.FtpTest.testConnection('testftp', 'ftp');

                return false;
            });

    document.getElementById('procengine')
            .addEventListener('change', akeebabackup.Restore.onProcEngineChange);


    akeebabackup.Restore.onProcEngineChange();

    // Work around Safari which ignores autocomplete=off
    setTimeout(akeebabackup.Restore.restoreDefaultOptions, 500);
});