/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

// Object initialisation
if (typeof akeebabackup == "undefined")
{
    var akeebabackup = {};
}

if (typeof akeebabackup.Wizard == "undefined")
{
    akeebabackup.Wizard = {
        URLs:        {},
        execTimes:   [30, 25, 20, 14, 7, 5, 3],
        blockSizes:  [240, 200, 160, 80, 40, 16, 4, 2, 1],
        translation: {}
    }
}

akeebabackup.Wizard.setIcon = function(step, state)
{
    const iconWait  = document.getElementById(`step-${step}-wait`)
    const iconRun   = document.getElementById(`step-${step}-run`)
    const iconDone  = document.getElementById(`step-${step}-done`)
    const iconError = document.getElementById(`step-${step}-error`)

    iconWait?.classList?.add('d-none');
    iconRun?.classList?.add('d-none');
    iconDone?.classList?.add('d-none');
    iconError?.classList?.add('d-none');

    switch (state)
    {
        default:
            iconWait?.classList?.remove('d-none');
            break;

        case "run":
            iconRun?.classList?.remove('d-none');
            break

        case "done":
            iconDone?.classList?.remove('d-none');
            break

        case "error":
            iconError?.classList?.remove('d-none');
            break
    }
}

/**
 * Boot up the Configuration Wizard benchmarking process
 */
akeebabackup.Wizard.boot = function ()
{
    akeebabackup.Wizard.execTimes  = [30, 25, 20, 14, 7, 5, 3];
    akeebabackup.Wizard.blockSizes = [480, 400, 240, 200, 160, 80, 40, 16, 4, 2, 1];

    // Show GUI
    document.getElementById("backup-progress-pane").style.display = "block";
    akeebabackup.Backup.resetTimeoutBar();

    // Before continuing, perform a call to the ping method, so Akeeba Backup knowns that it was configured
    akeebabackup.System.doAjax(
        {
            act: "ping"
        },
        function ()
        {
            akeebabackup.Wizard.flush();
        },
        function ()
        {
        },
        false,
        10000
    );
};

akeebabackup.Wizard.flush = function()
{
    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar(30000, 100);

    akeebabackup.Wizard.setIcon('flush', 'run');

    akeebabackup.System.doAjax(
        {act: 'flush'},
        function(msg) {
            console.log(msg);

            akeebabackup.Wizard.setIcon('flush', 'done');

            akeebabackup.Wizard.minExec();
        },
        function (msg) {
            console.log(msg);

            akeebabackup.Wizard.setIcon('flush', 'done');

            akeebabackup.Wizard.minExec();
        }
    );

    // akeebabackup.Wizard.minExec();
}

/**
 * Determine the optimal Minimum Execution Time
 *
 * @param   seconds     How many seconds to test
 * @param   repetition  Which try is this?
 */
akeebabackup.Wizard.minExec = function (seconds, repetition)
{
    if (seconds == null)
    {
        seconds = 0;
    }
    if (repetition == null)
    {
        repetition = 0;
    }

    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar((2 * seconds + 5) * 1000, 100);

    document.getElementById("backup-substep").textContent =
        Joomla.Text._('COM_AKEEBABACKUP_CONFWIZ_UI_MINEXECTRY').replace("%s", seconds.toFixed(1));

    akeebabackup.Wizard.setIcon('minexec', 'run');

    akeebabackup.System.doAjax(
        {act: "minexec", "seconds": seconds},
        function (msg)
        {
            // The ping was successful. Add a repetition count.
            repetition++;
            if (repetition < 3)
            {
                // We need more repetitions
                akeebabackup.Wizard.minExec(seconds, repetition);
            }
            else
            {
                // Three repetitions reached. Success!
                akeebabackup.Wizard.minExecApply(seconds);
            }
        },
        function ()
        {
            // We got a failure. Add half a second
            seconds += 0.5;

            if (seconds > 20)
            {
                // Uh-oh... We exceeded our maximum allowance!
                document.getElementById("backup-progress-pane").style.display = "none";
                document.getElementById("error-panel").style.display          = "block";
                document.getElementById("backup-error-message").textContent   =
                    Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTDETERMINEMINEXEC");
            }
            else
            {
                akeebabackup.Wizard.minExec(seconds, 0);
            }
        },
        false,
        (2 * seconds + 5) * 1000
    );
};

/**
 * Applies the AJAX preference and the minimum execution time determined in the previous steps
 *
 * @param   seconds  The minimum execution time, in seconds
 */
akeebabackup.Wizard.minExecApply = function (seconds)
{
    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar(25000, 100);

    document.getElementById("backup-substep").textContent =
        Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_SAVEMINEXEC");

    akeebabackup.System.doAjax(
        {act: "applyminexec", "minexec": seconds},
        function (msg)
        {
            akeebabackup.Wizard.setIcon('minexec', 'done');

            akeebabackup.Wizard.directories();
        },
        function ()
        {
            akeebabackup.Wizard.setIcon('minexec', 'error');

            // Unsuccessful call. Oops!
            document.getElementById("backup-progress-pane").style.display = "none";
            document.getElementById("error-panel").style.display          = "block";
            document.getElementById("backup-error-message").textContent   =
                Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTSAVEMINEXEC");
        },
        false
    );
};

/**
 * Automatically determine the optimal output and temporary directories,
 * then make sure they are writable
 */
akeebabackup.Wizard.directories = function ()
{
    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar(10000, 100);

    document.getElementById("backup-substep").innerHTML = "";

    akeebabackup.Wizard.setIcon('directory', 'run');

    akeebabackup.System.doAjax(
        {act: "directories"},
        function (msg)
        {
            if (msg?.status)
            {
                akeebabackup.Wizard.setIcon('directory', 'done');

                akeebabackup.Wizard.database();

                return;
            }

            document.getElementById("backup-progress-pane").style.display = "none";
            document.getElementById("error-panel").style.display          = "block";
            document.getElementById("backup-error-message").textContent   =
                Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTFIXDIRECTORIES");
        },
        function ()
        {
            akeebabackup.Wizard.setIcon('directory', 'error');

            document.getElementById("backup-progress-pane").style.display = "none";
            document.getElementById("error-panel").style.display          = "block";
            document.getElementById("backup-error-message").textContent   =
                Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTFIXDIRECTORIES");
        },
        false
    );
};

/**
 * Determine the optimal database dump options, analyzing the site's database
 */
akeebabackup.Wizard.database = function ()
{
    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar(30000, 50);

    document.getElementById("backup-substep").innerHTML = "";

    akeebabackup.Wizard.setIcon('dbopt', 'run');

    akeebabackup.System.doAjax(
        {act: "database"},
        function (msg)
        {
            // !!!! THIS CAN NEVER BE FALSE !!!!
            // if (!msg)
            // {
            //     document.getElementById("backup-progress-pane").style.display = "none";
            //     document.getElementById("error-panel").style.display          = "block";
            //     document.getElementById("backup-error-message").textContent   =
            //         Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTDBOPT");
            //
            //     return;
            // }

            akeebabackup.Wizard.setIcon('dbopt', 'done');

            akeebabackup.Wizard.maxExec();
        },
        function ()
        {
            akeebabackup.Wizard.setIcon('dbopt', 'error');

            document.getElementById("backup-progress-pane").style.display = "none";
            document.getElementById("error-panel").style.display          = "block";
            document.getElementById("backup-error-message").textContent   =
                Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTDBOPT");
        },
        false
    );
};

/**
 * Determine the optimal maximum execution time which doesn't cause a timeout or server error
 */
akeebabackup.Wizard.maxExec = function ()
{
    var exec_time = akeebabackup.Wizard.execTimes.shift();

    if ((akeebabackup.Wizard.execTimes.length === 0) || (exec_time == null))
    {
        // Darn, we ran out of options
        document.getElementById("backup-progress-pane").style.display = "none";
        document.getElementById("error-panel").style.display          = "block";
        document.getElementById("backup-error-message").textContent   =
            Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_EXECTOOLOW");

        return;
    }

    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar((exec_time * 1.2) * 1000, 80);

    akeebabackup.Wizard.setIcon('maxexec', 'run');

    document.getElementById("backup-substep").textContent =
        Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_MINEXECTRY").replace("%s", exec_time.toFixed(0));

    akeebabackup.System.doAjax(
        {act: "maxexec", "seconds": exec_time},
        function (msg)
        {
            if (msg?.status)
            {
                // Success! Save this value.
                akeebabackup.Wizard.maxExecApply(exec_time);
            }
            else
            {
                // Uh... we have to try something lower than that
                akeebabackup.Wizard.maxExec();
            }
        },
        function ()
        {
            // Uh... we have to try something lower than that
            akeebabackup.Wizard.maxExec();
        },
        false,
        38000 // Maximum time to wait: 38 seconds
    );
};

/**
 * Apply the maximum execution time
 *
 * @param   seconds  The number of max execution time (in seconds) we found that works on the server
 */
akeebabackup.Wizard.maxExecApply = function (seconds)
{
    akeebabackup.Backup.resetTimeoutBar();
    akeebabackup.Backup.startTimeoutBar(10000, 100);

    document.getElementById("backup-substep").textContent =
        Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_SAVINGMAXEXEC");

    akeebabackup.System.doAjax(
        {act: "applymaxexec", "seconds": seconds},
        function ()
        {
            akeebabackup.Wizard.setIcon('maxexec', 'done');

            akeebabackup.Wizard.partSize();
        },
        function ()
        {
            akeebabackup.Wizard.setIcon('maxexec', 'error');

            document.getElementById("backup-progress-pane").style.display = "none";
            document.getElementById("error-panel").style.display          = "block";
            document.getElementById("backup-error-message").textContent   =
                Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTSAVEMAXEXEC");
        }
    );
};

/**
 * Try to find the best part size for split archives which works on this server
 */
akeebabackup.Wizard.partSize = function ()
{
    akeebabackup.Backup.resetTimeoutBar();

    var block_size = akeebabackup.Wizard.blockSizes.shift();

    if ((akeebabackup.Wizard.blockSizes.length === 0) || (block_size == null))
    {
        // Uh... I think you are running out of disk space, dude
        document.getElementById("backup-progress-pane").style.display = "none";
        document.getElementById("error-panel").style.display          = "block";
        document.getElementById("backup-error-message").textContent   =
            Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_CANTDETERMINEPARTSIZE");

        return;
    }

    var part_size = block_size / 8; // Translate to Mb

    akeebabackup.Backup.startTimeoutBar(30000, 100);
    document.getElementById("backup-substep").textContent =
        Joomla.Text._("COM_AKEEBABACKUP_CONFWIZ_UI_PARTSIZE").replace("%s", part_size.toFixed(3));

    akeebabackup.Wizard.setIcon('splitsize', 'run');

    akeebabackup.System.doAjax(
        {act: "partsize", blocks: block_size},
        function (msg)
        {
            if (msg?.status)
            {
                // We are done
                akeebabackup.Wizard.setIcon('splitsize', 'done');

                akeebabackup.Wizard.done();

                return;
            }

            // Let's try the next (lower) value
            akeebabackup.Wizard.partSize();
        },
        function (msg)
        {
            // The server blew up on our face. Let's try the next (lower) value.
            akeebabackup.Wizard.partSize();
        },
        false,
        60000
    );
};

/**
 * The configuration wizard is done
 */
akeebabackup.Wizard.done = function ()
{
    document.getElementById("backup-progress-pane").style.display = "none";
    document.getElementById("backup-complete").style.display      = "block";
};

akeebabackup.System.documentReady( function ()
{
    akeebabackup.Wizard.boot();
});