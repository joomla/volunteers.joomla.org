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

if (typeof akeebabackup.Backup == "undefined")
{
    akeebabackup.Backup = {
        tag:           "",
        backupid:      null,
        backupToken:   null,
        currentDomain: null,
        returnUrl:     "",
        timeoutTimer:  null,
        resumeTimer:   null,
        resume:        {
            retry:        0,
            showWarnings: 0
        }
    };
}

/**
 * Start the timer which launches the next backup step. This allows us to prevent deep nesting of AJAX calls which could
 * lead to performance issues on long backups.
 *
 * @param   waitTime  How much time to wait before starting a backup step, in msec (default: 10)
 */
akeebabackup.Backup.timer = function (waitTime)
{
    if (waitTime <= 0)
    {
        waitTime = 10;
    }

    setTimeout(akeebabackup.Backup.timerTick, waitTime);
};

/**
 * This is used by the timer() method to run the next backup step
 */
akeebabackup.Backup.timerTick = function ()
{
    try
    {
        console.log("Timer tick");
    }
    catch (e)
    {
    }

    // Reset the timer
    akeebabackup.Backup.resetTimeoutBar();
    var maxExecutionTime = Joomla.getOptions("akeebabackup.Backup.maxExecutionTime", 14);
    var runtimeBias      = Joomla.getOptions("akeebabackup.Backup.runtimeBias", 75);
    akeebabackup.Backup.startTimeoutBar(maxExecutionTime, runtimeBias);

    // Run the step
    akeebabackup.System.doAjax({
        ajax:        "step",
        tag:         akeebabackup.Backup.tag,
        backupid:    akeebabackup.Backup.backupid,
        backupToken: akeebabackup.Backup.backupToken
    }, akeebabackup.Backup.onStep, akeebabackup.Backup.onError, false);
};

/**
 * Starts the timer for the last response timer
 *
 * @param   max_allowance  Maximum time allowance in seconds
 * @param   bias           Runtime bias in %
 */
akeebabackup.Backup.startTimeoutBar = function (max_allowance, bias)
{
    var lastResponseSeconds = 0;

    akeebabackup.Backup.timeoutTimer = setInterval(function ()
    {
        lastResponseSeconds++;

        var responseTimer = document.querySelector("#response-timer div.text");

        if (responseTimer)
        {
            responseTimer.textContent = Joomla.Text._(
                "COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE").replace(
                "%s", lastResponseSeconds.toFixed(0)
            );
        }
    }, 1000);
};

/**
 * Resets the last response timer bar
 */
akeebabackup.Backup.resetTimeoutBar = function ()
{
    try
    {
        clearInterval(akeebabackup.Backup.timeoutTimer);
    }
    catch (e)
    {
    }

    var responseTimer = document.querySelector("#response-timer div.text");

    if (responseTimer)
    {
        responseTimer.textContent = Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE").replace("%s", "0");
    }
};

/**
 * Starts the timer for the last response timer
 */
akeebabackup.Backup.startRetryTimeoutBar = function ()
{
    var remainingSeconds = Joomla.getOptions("akeebabackup.Backup.resume.timeout", 10);

    akeebabackup.Backup.resumeTimer = setInterval(function ()
    {
        remainingSeconds--;
        document.getElementById(
            "akeebabackup-retry-timeout").textContent = remainingSeconds.toFixed(0);

        if (remainingSeconds === 0)
        {
            clearInterval(akeebabackup.Backup.resumeTimer);
            akeebabackup.Backup.resumeBackup();
        }
    }, 1000);
};

/**
 * Resets the last response timer bar
 */
akeebabackup.Backup.resetRetryTimeoutBar = function ()
{
    clearInterval(akeebabackup.Backup.resumeTimer);

    var timeout = Joomla.getOptions("akeebabackup.Backup.resume.timeout", 10);

    document.getElementById("akeebabackup-retry-timeout").textContent = timeout.toFixed(0);
};

/**
 * Renders the list of the backup steps
 *
 * @param   active_step  Which is the active step?
 */
akeebabackup.Backup.renderBackupSteps = function (active_step)
{
    var normal_class = "bg-success text-white";

    if (active_step == "")
    {
        normal_class = "bg-light text-dark";
    }

    document.getElementById("backup-steps").innerHTML = "";

    var backupDomains = Joomla.getOptions("akeebabackup.Backup.domains", {});

    for (var counter = 0; counter < backupDomains.length; counter++)
    {
        var element = backupDomains[counter];

        var step       = document.createElement("div");
        step.className = "mt-1 mb-1 p-1 border rounded";
        step.innerHTML = element[1];
        document.getElementById("backup-steps").appendChild(step);

        if (element[0] == active_step)
        {
            normal_class = "bg-light text-dark";
            this_class   = "bg-primary text-white";
        }
        else
        {
            var this_class = normal_class;
        }

        step.className += " " + this_class;
    }
};

/**
 * Start the backup
 */
akeebabackup.Backup.start = function ()
{
    try
    {
        console.log("Starting backup");
        console.log(data);
    }
    catch (e)
    {
    }

    // Check for AVG Link Scanner
    if (window.AVGRUN)
    {
        try
        {
            console.warn("AVG Antivirus with Link Checker detected. The backup WILL fail!");
        }
        catch (e)
        {
        }


        var r = confirm(Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_AVGWARNING"));

        if (!r)
        {
            return false;
        }
    }

    // Hide the backup setup
    document.getElementById("backup-setup").style.display         = "none";
    // Show the backup progress
    document.getElementById("backup-progress-pane").style.display = "block";

    // Let's check if we have a password even if we didn't set it in the profile (maybe a password manager filled it?)
    var hasAngieKey = Joomla.getOptions("akeebabackup.Backup.hasAngieKey", false);

    if (hasAngieKey)
    {
        document.getElementById("angie-password-warning").style.display = "block";
    }

    // Show desktop notification
    var rightNow = new Date();
    akeebabackup.System.notification.notify(Joomla.Text._(
        "COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPSTARTED") + " " + rightNow.toLocaleString());

    // Initialize steps
    akeebabackup.Backup.renderBackupSteps("");
    // Start the response timer
    var maxExecutionTime = Joomla.getOptions("akeebabackup.Backup.maxExecutionTime", 14);
    var runtimeBias      = Joomla.getOptions("akeebabackup.Backup.runtimeBias", 75);
    akeebabackup.Backup.startTimeoutBar(maxExecutionTime, runtimeBias);
    // Perform Ajax request
    var ajax_request = {
        // Data to send to AJAX
        "ajax":      "start",
        description: document.getElementById("backup-description").value,
        comment:     document.getElementById("comment").value
    };

    akeebabackup.System.doAjax(ajax_request, akeebabackup.Backup.onStep, akeebabackup.Backup.onError, false);

    return false;
};

/**
 * Backup step callback handler
 *
 * @param   data  Backup data received
 */
akeebabackup.Backup.onStep = function (data)
{
    try
    {
        console.log("Running backup step");
        console.log(data);
    }
    catch (e)
    {
    }

    // Update visual step progress from active domain data
    akeebabackup.Backup.renderBackupSteps(data.Domain);
    akeebabackup.Backup.currentDomain = data.Domain;

    // Update percentage display
    var percentageText = data.Progress + "%";

    var elProgress         = document.querySelector("#backup-percentage div.progress-bar");
    elProgress.style.width = data.Progress + "%";
    elProgress.setAttribute('aria-valuenow', percentageText);
    elProgress.innerHTML = percentageText;

    // Update step/substep display
    document.getElementById("backup-step").textContent    = data.Step;
    document.getElementById("backup-substep").textContent = data.Substep;

    // Do we have warnings?
    data.Warnings = data.Warnings || [];

    if (data.Warnings.length > 0)
    {
        var barClass = document.getElementById("backup-percentage").className;

        if (barClass.indexOf("bg-warning") == -1)
        {
            document.getElementById("backup-percentage").className += " bg-warning";
        }

        for (var i = 0; i < data.Warnings.length; i++)
        {
            var warning = data.Warnings[i];

            akeebabackup.System.notification.notify(
                Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPWARNING"), warning);

            var newDiv         = document.createElement("div");
            newDiv.className = 'mt-2 border-bottom pt-1 pb-1';
            newDiv.textContent = warning;
            document.getElementById("warnings-list").appendChild(newDiv);
        }

        document.getElementById("backup-warnings-panel").style.display = "block";
    }

    // Do we have errors?
    var error_message = data.Error;

    if (error_message != "")
    {
        try
        {
            console.error("Got an error message");
            console.log(error_message);
        }
        catch (e)
        {
        }

        // Uh-oh! An error has occurred.
        akeebabackup.Backup.onError(error_message);

        return;
    }

    // No errors. Good! Are we finished yet?
    if (data["HasRun"] == 1)
    {
        try
        {
            console.log("Backup complete");
            console.log(error_message);
        }
        catch (e)
        {
        }

        // Yes. Show backup completion page.
        akeebabackup.Backup.onDone();

        return;
    }

    // No. Set the backup tag
    if (akeebabackup.Backup.tag == '')
    {
        akeebabackup.Backup.tag = "backend";
    }

    // Set the backup id
    akeebabackup.Backup.backupid = data.backupid;

    // Capture the per-backup anti-CSRF token issued when the backup starts. It authenticates the subsequent steps.
    if (data.backupToken)
    {
        akeebabackup.Backup.backupToken = data.backupToken;
    }

    // Reset the retries
    akeebabackup.Backup.resume.retry = 0;

    // How much time do I have to wait?
    var waitTime = 10;

    if (data.hasOwnProperty("sleepTime"))
    {
        waitTime = data.sleepTime;
    }

    // ...and send an AJAX command
    try
    {
        console.log("Starting tick timer with waitTime = " + waitTime + " msec");
    }
    catch (e)
    {
    }

    akeebabackup.Backup.timer(waitTime);
};

/**
 * Resume a backup attempt after an AJAX error has occurred.
 */
akeebabackup.Backup.resumeBackup = function ()
{
    // Make sure the timer is stopped
    akeebabackup.Backup.resetRetryTimeoutBar();

    // Hide error and retry panels
    document.getElementById("error-panel").style.display = "none";
    document.getElementById("retry-panel").style.display = "none";

    // Show progress and warnings
    document.getElementById("backup-progress-pane").style.display = "block";

    // Only display warnings if the saved state of warnings is true
    if (akeebabackup.Backup.resume.showWarnings)
    {
        document.getElementById("backup-warnings-panel").style.display = "block";
    }

    var rightNow = new Date();
    akeebabackup.System.notification.notify(Joomla.Text._(
        "COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPRESUME") + " " + rightNow.toLocaleString());

    // Restart the backup
    akeebabackup.Backup.timer();

    return false;
};

/**
 * Cancel the automatic resumption of a backup attempt after an AJAX error has occurred
 */
akeebabackup.Backup.cancelResume = function ()
{
    // Make sure the timer is stopped
    akeebabackup.Backup.resetRetryTimeoutBar();

    // Kill the backup
    var errorMessage = document.getElementById("backup-error-message-retry").innerHTML;
    akeebabackup.Backup.endWithError(errorMessage);

    return false;
};

/**
 * AJAX error callback
 *
 * @param   message  The error message received
 */
akeebabackup.Backup.onError = function (message)
{
    // If resume is not enabled, die.
    if (!Joomla.getOptions("akeebabackup.Backup.resume.enabled", true))
    {
        akeebabackup.Backup.endWithError(message);

        return;
    }

    // If we are past the max retries, die.
    if (akeebabackup.Backup.resume.retry >= Joomla.getOptions("akeebabackup.Backup.resume.maxRetries", 3))
    {
        akeebabackup.Backup.endWithError(message);

        return;
    }

    // Make sure the timer is stopped
    akeebabackup.Backup.resume.retry++;
    akeebabackup.Backup.resetRetryTimeoutBar();

    var resumeNotificationMessage         = Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPHALT_DESC");
    var timeout                           = Joomla.getOptions(
        "akeebabackup.Backup.resume.timeout", 10);
    var resumeNotificationMessageReplaced = resumeNotificationMessage.replace(
        "%d", timeout.toFixed(0));
    akeebabackup.System.notification.notify(
        Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPHALT"), resumeNotificationMessageReplaced);

    // Save display state of warnings panel
    akeebabackup.Backup.resume.showWarnings =
        (document.getElementById("backup-warnings-panel").style.display !== "none");

    // Hide progress and warnings
    document.getElementById("backup-progress-pane").style.display  = "none";
    document.getElementById("backup-warnings-panel").style.display = "none";
    document.getElementById("error-panel").style.display           = "none";

    // Setup and show the retry pane
    document.getElementById("backup-error-message-retry").textContent = message;
    document.getElementById("retry-panel").style.display              = "block";

    // Start the countdown
    akeebabackup.Backup.startRetryTimeoutBar();
};

/**
 * Terminate the backup with an error
 *
 * @param   message  The error message received
 */
akeebabackup.Backup.endWithError = function (message)
{
    // Make sure the timer is stopped
    akeebabackup.Backup.resetTimeoutBar();

    var alice_autorun = false;

    // Hide progress and warnings
    document.getElementById("backup-progress-pane").style.display  = "none";
    document.getElementById("backup-warnings-panel").style.display = "none";
    document.getElementById("retry-panel").style.display           = "none";

    // Set up the view log URL
    var logURL     = Joomla.getOptions("akeebabackup.Backup.URLs.LogURL", "");
    var viewLogUrl = logURL + "&tag=" + akeebabackup.Backup.tag;
    var aliceUrl   = Joomla.getOptions(
        "akeebabackup.Backup.URLs.AliceURL", "") + "&log=" + akeebabackup.Backup.tag;

    if (akeebabackup.Backup.backupid)
    {
        viewLogUrl = viewLogUrl + "." + encodeURIComponent(akeebabackup.Backup.backupid);
        aliceUrl   = aliceUrl + "." + encodeURIComponent(akeebabackup.Backup.backupid);
    }

    if (akeebabackup.Backup.currentDomain == "finale")
    {
        alice_autorun = true;
        aliceUrl += "&autorun=1";
    }

    document.getElementById("ab-viewlog-error").setAttribute("href", viewLogUrl);
    document.getElementById("ab-alice-error").setAttribute("href", aliceUrl);

    akeebabackup.System.notification.notify(Joomla.Text._("COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPFAILED"), message);

    // Try to send a push notification for failed backups
    akeebabackup.System.doAjax({
        "ajax":         "pushFail",
        "tag":          akeebabackup.Backup.tag,
        "backupid":     akeebabackup.Backup.backupid,
        "backupToken":  akeebabackup.Backup.backupToken,
        "errorMessage": message
    }, function (msg)
    {
    });

    // Setup and show error pane
    document.getElementById("backup-error-message").textContent = message;
    document.getElementById("error-panel").style.display        = "block";

    // Do we have to automatically analyze the log?
    if (alice_autorun)
    {
        setTimeout(function ()
        {
            window.location = aliceUrl;
        }, 500);
    }
};

/**
 * Backup finished callback handler
 */
akeebabackup.Backup.onDone = function ()
{
    var rightNow = new Date();
    akeebabackup.System.notification.notify(Joomla.Text._(
        "COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPFINISHED") + " " + rightNow.toLocaleString());

    // Make sure the timer is stopped
    akeebabackup.Backup.resetTimeoutBar();

    // Hide progress
    document.getElementById("backup-progress-pane").style.display = "none";

    // Show finished pane
    document.getElementById("backup-complete").style.display     = "block";
    document.getElementById("backup-warnings-panel").style.width = "100%";

    // Show correct log URL
    var logURL     = Joomla.getOptions("akeebabackup.Backup.URLs.LogURL", "");
    var viewLogUrl = logURL + "&tag=" + akeebabackup.Backup.tag;

    // If the backup completes in a single pageload the backup tag and backupid are not returned. So I need to cheat.
    if (!akeebabackup.Backup.tag)
    {
        viewLogUrl = logURL + "&latest=1";
    }
    else if (akeebabackup.Backup.backupid)
    {
        viewLogUrl = viewLogUrl + "." + encodeURIComponent(akeebabackup.Backup.backupid);
    }

    try
    {
        document.getElementById("ab-viewlog-success").setAttribute("href", viewLogUrl);
    }
    catch (e)
    {
    }

    // Proceed to the return URL if it is set

    var returnUrl = Joomla.getOptions("akeebabackup.Backup.returnUrl", akeebabackup.Backup.returnUrl);

    if (returnUrl != "")
    {
        window.location = returnUrl;
    }
};

akeebabackup.Backup.restoreDefaultOptions = function ()
{
    document.getElementById("backup-description").value =
        Joomla.getOptions("akeebabackup.Backup.defaultDescription", "");

    document.getElementById("comment").value = "ThisIsADummyStringToWorkAroundChrome";
    document.getElementById("comment").value = "";
};

akeebabackup.Backup.flipProfile = function ()
{
    // Save the description and comments
    document.getElementById("flipDescription").value = document.getElementById("backup-description").value;
    document.getElementById("flipComment").value     = document.getElementById("comment").value;

    // The timeout is necessary. The choice event is fired before the hidden SELECT element is updated. There is no
    // event after that change takes place. Therefore we need to wait a little bit for the change to take effect.
    setTimeout(function () {
        document.forms.flipForm.submit();
    }, 500);
};

akeebabackup.System.documentReady(function ()
{
    // Browser notifications: ask for permission if the feature is enabled
    akeebabackup.System.notification.askPermission();

    // Register event handlers
    akeebabackup.System.addEventListener("comAkeebaControlPanelProfileSwitch", "choice", akeebabackup.Backup.flipProfile);

    akeebabackup.System.addEventListener("comAkeebaBackupCancelResume", "click", akeebabackup.Backup.cancelResume);
    akeebabackup.System.addEventListener("comAkeebaBackupResumeBackup", "click", akeebabackup.Backup.resumeBackup);

    if (Joomla.getOptions("akeebabackup.Backup.autostart"))
    {
        // Auto-start the backup: run backup now, skip registering events for the backup start / reset buttons
        akeebabackup.Backup.start();
    }
    else
    {
        // Bind start button's click event
        akeebabackup.System.addEventListener("backup-start", "click", akeebabackup.Backup.start);
        akeebabackup.System.addEventListener("backup-default", "click", akeebabackup.Backup.restoreDefaultOptions);
    }
});