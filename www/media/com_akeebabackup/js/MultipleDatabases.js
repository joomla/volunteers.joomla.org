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

if (typeof akeebabackup.Multidb == "undefined")
{
    akeebabackup.Multidb = {
        modalDialog: null
    }
}

/**
 * Render the additional databases interface
 *
 * @param data
 */
akeebabackup.Multidb.render = function (data)
{
    var tbody       = document.getElementById("ak_list_contents");
    tbody.innerHTML = "";

    for (var rootname in data)
    {
        if (!data.hasOwnProperty(rootname))
        {
            continue;
        }

        var def = data[rootname];

        akeebabackup.Multidb.addRow(rootname, def, tbody);
    }

    akeebabackup.Multidb.addNewRecordButton(tbody);
};

/**
 * Add a single row to the additional databases interface
 *
 * @param root
 * @param def
 * @param append_to_here
 */
akeebabackup.Multidb.addRow = function (root, def, append_to_here)
{
    var elTr = document.createElement("tr");

    elTr.className       = "ak_filter_row";
    elTr.dataset["root"] = root;
    elTr.dataset["def"]  = JSON.stringify(def);

    // Delete button
    var elTdDelete         = document.createElement("td");
    elTdDelete.style.width = "2em";

    var elDeleteSpan       = document.createElement("span");
    elDeleteSpan.className = "ak_filter_tab_icon_container btn btn-danger btn-sm";
    elDeleteSpan.addEventListener("click", function ()
    {
        var elRootNode = this.parentNode.parentNode;

        var new_data = {
            root: elRootNode.dataset["root"],
            verb: "remove"
        };

        akeebabackup.Fsfilters.toggle(
            new_data,
            this,
            function (response, caller)
            {
                if (response.success === true)
                {
                    var elRemove = caller.parentNode.parentNode;
                    elRemove.parentNode.removeChild(elRemove);
                }
            }
        );
    });

    var elDeleteIcon       = document.createElement("span");
    elDeleteIcon.className = "ak-toggle-button deletebutton";
    elDeleteIcon.insertAdjacentHTML("beforeend", "<span class=\"fa fa-trash\"></span>");

    elDeleteSpan.appendChild(elDeleteIcon);

    elTdDelete.appendChild(elDeleteSpan);

    // Edit button
    var elTdEdit         = document.createElement("td");
    elTdEdit.style.width = "2em";

    var elEditSpan       = document.createElement("span");
    elEditSpan.className = "ak_filter_tab_icon_container btn btn-primary btn-sm";
    elEditSpan.addEventListener("click", function ()
    {
        var cache_element = this.parentNode.parentNode;
        var cache_data    = JSON.parse(cache_element.dataset["def"] ?? "{}");
        var cache_root    = cache_element.dataset["root"];
        var editor        = document.getElementById("akEditorDialog");

        // Select the correct driver
        if (cache_data.driver === "")
        {
            cache_data.driver = "mysqli";
        }

        // Set the parameters
        document.getElementById("ake_driver").value   = cache_data.driver;
        document.getElementById("ake_host").value     = cache_data.host;
        document.getElementById("ake_username").value = cache_data.username;
        document.getElementById("ake_password").value = cache_data.password;
        document.getElementById("ake_database").value = cache_data.database;
        document.getElementById("ake_prefix").value   = cache_data.prefix;

        // Remove any leftover notifier
        try
        {
            var elRemove = document.getElementById("ak_editor_notifier");
            elRemove.parentNode.removeChild(elRemove);
        }
        catch (e)
        {
        }

        // Test connection button
        /**
         * Node cloning removes leftover event listeners preventing the bug in tickets #28300 and #28317.
         *
         * See https://stackoverflow.com/questions/9251837/how-to-remove-all-listeners-in-an-element
         */
        var elEditorDefaultOld = document.getElementById("akEditorBtnDefault");
        var elEditorDefault    = elEditorDefaultOld.cloneNode(true);
        elEditorDefaultOld.parentNode.replaceChild(elEditorDefault, elEditorDefaultOld);

        elEditorDefault.addEventListener("click", function ()
        {
            // Remove any leftover notifier
            try
            {
                var elRemove = document.getElementById("ak_editor_notifier");
                elRemove.parentNode.removeChild(elRemove);
            }
            catch (e)
            {
            }

            // Create the placeholder div and show a loading message
            var elAlertDiv       = document.createElement("div");
            elAlertDiv.className = "alert alert-info";
            elAlertDiv.id        = "ak_editor_notifier";

            var elSpanNotifierContent = document.createElement("p");
            elSpanNotifierContent.id  = "ak_editor_notifier_content";
            elAlertDiv.appendChild(elSpanNotifierContent);

            var elSpinner = document.createElement("img");
            elSpinner.setAttribute("border", 0);
            elSpinner.setAttribute("src", Joomla.getOptions("akeebabackup.Multidb.loadingGif", ""));
            elSpanNotifierContent.appendChild(elSpinner);

            var elLoadingText         = document.createElement("span");
            elLoadingText.textContent = Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_LOADING");
            elSpanNotifierContent.appendChild(elLoadingText);

            var elEditorTable = document.getElementById("ak_editor_table");
            elEditorTable.insertAdjacentHTML("beforebegin", elAlertDiv.outerHTML);

            // Test the connection via AJAX
            var elDriverDropdown = document.getElementById("ake_driver");
            var elSelectedOption = elDriverDropdown.options[elDriverDropdown.selectedIndex];
            var driver           = (elSelectedOption == null) ? "" : elSelectedOption.value;
            var req              = {
                verb: "test",
                root: root,
                data: {
                    host:     document.getElementById("ake_host").value,
                    driver:   driver,
                    port:     document.getElementById("ake_port").value,
                    user:     document.getElementById("ake_username").value,
                    password: document.getElementById("ake_password").value,
                    database: document.getElementById("ake_database").value,
                    prefix:   document.getElementById("ake_prefix").value
                }
            };

            var query = {
                action: JSON.stringify(req)
            };

            akeebabackup.System.doAjax(query, function (response)
            {
                var elEditorNotifierContent = document.getElementById("ak_editor_notifier_content");

                if (response.status === true)
                {
                    document.getElementById("ak_editor_notifier").className = "alert alert-success";

                    elEditorNotifierContent.textContent =
                        Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CONNECTOK");
                }
                else
                {
                    document.getElementById("ak_editor_notifier").className = "alert alert-danger";

                    elEditorNotifierContent.innerHTML =
                        Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CONNECTFAIL") +
                        "<br/>" +
                        "<code>" + response.message + "</code>";
                }
            }, function (message)
            {
                var elEditorNotifierContent = document.getElementById("ak_editor_notifier_content");

                document.getElementById("ak_editor_notifier").className = "alert alert-danger";
                elEditorNotifierContent.textContent                     =
                    Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CONNECTFAIL")

                if ((typeof akeebabackup.Multidb.modalDialog == "object") && akeebabackup.Multidb.modalDialog.hide)
                {
                    akeebabackup.Multidb.modalDialog.hide();
                }

                akeebabackup.System.params.errorCallback(message);
            }, false, 15000);
        });

        // Save button
        /**
         * Node cloning removes leftover event listeners preventing the bug in tickets #28300 and #28317.
         *
         * See https://stackoverflow.com/questions/9251837/how-to-remove-all-listeners-in-an-element
         */
        var elEditorSaveOld = document.getElementById("akEditorBtnSave");
        var elEditorSave    = elEditorSaveOld.cloneNode(true);
        elEditorSaveOld.parentNode.replaceChild(elEditorSave, elEditorSaveOld);

        elEditorSave.addEventListener("click", function ()
        {
            // Remove any leftover notifier
            try
            {
                var elRemove = document.getElementById("ak_editor_notifier");
                elRemove.parentNode.removeChild(elRemove);
            }
            catch (e)
            {
            }

            var elAlertDiv       = document.createElement("div");
            elAlertDiv.className = "alert alert-info";
            elAlertDiv.id        = "ak_editor_notifier";

            var elSpanNotifierContent = document.createElement("p");
            elSpanNotifierContent.id  = "ak_editor_notifier_content";
            elAlertDiv.appendChild(elSpanNotifierContent);

            var elSpinner = document.createElement("img");
            elSpinner.setAttribute("border", 0);
            elSpinner.setAttribute("src", Joomla.getOptions("akeebabackup.Multidb.loadingGif", ""));
            elSpanNotifierContent.appendChild(elSpinner);

            var elLoadingText         = document.createElement("span");
            elLoadingText.textContent = Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_LOADING");
            elSpanNotifierContent.appendChild(elLoadingText);

            var elEditorTable = document.getElementById("ak_editor_table");
            elEditorTable.insertAdjacentHTML("beforebegin", elAlertDiv.outerHTML);

            // Send AJAX save request
            var elDriverDropdown = document.getElementById("ake_driver");
            var elSelectedOption = elDriverDropdown.options[elDriverDropdown.selectedIndex];
            var driver           = (elSelectedOption == null) ? "" : elSelectedOption.value;
            var req              = {
                verb: "set",
                root: root,
                data: {
                    host:     document.getElementById("ake_host").value,
                    driver:   driver,
                    port:     document.getElementById("ake_port").value,
                    username: document.getElementById("ake_username").value,
                    password: document.getElementById("ake_password").value,
                    database: document.getElementById("ake_database").value,
                    prefix:   document.getElementById("ake_prefix").value,
                    dumpFile: String(root).substr(0, 9) + document.getElementById("ake_database").value + ".sql"
                }
            };

            // If the host and database name are both empty treat this as a cancel event
            if (!req.data.host.length && !req.data.database.length)
            {
                akeebabackup.System.triggerEvent('akEditorBtnCancel', 'click');

                return;
            }

            var query = {
                action: JSON.stringify(req)
            };

            akeebabackup.System.doAjax(query, function (response)
            {
                if ((response?.success ?? false) !== true)
                {
                    document.getElementById("ak_editor_notifier_content").textContent =
                        Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_SAVEFAIL");

                    return;
                }

                // Cache new data
                cache_element.dataset["def"] = JSON.stringify(req.data);

                // Update grid cells (host & db)
                var cells = cache_element.querySelectorAll("td");

                cache_element.querySelector("span.ak_dbhost").textContent = req.data.host;
                cache_element.querySelector("span.ak_dbname").textContent = req.data.database;

                // Handle new row case
                if (!cache_element.querySelector("span.editbutton").firstChild.classList.contains("fa-edit"))
                {
                    // This was a new row. Add the normal buttons...
                    cache_element.querySelector("span.deletebutton").parentNode.style.display = "inline-block";

                    var elEditIcon                  = cache_element.querySelector("span.editbutton");
                    elEditIcon.firstChild.className = "fa fa-edit";

                    // ...then add a new "add new row" at the bottom.
                    akeebabackup.Multidb.addNewRecordButton(cache_element.parentNode);
                }

                // Finally close the dialog
                if ((typeof akeebabackup.Multidb.modalDialog == "object") && akeebabackup.Multidb.modalDialog.hide)
                {
                    akeebabackup.Multidb.modalDialog.hide();
                }

            }, function (message)
            {
                document.getElementById("ak_editor_notifier_content").textContent =
                    Joomla.Text._("COM_AKEEBABACKUP_MULTIDB_GUI_LBL_SAVEFAIL");

                if ((typeof akeebabackup.Multidb.modalDialog == "object") && akeebabackup.Multidb.modalDialog.hide)
                {
                    akeebabackup.Multidb.modalDialog.hide();
                }

                akeebabackup.System.params.errorCallback(message);
            }, false, 15000);
        });

        // Cancel button
        /**
         * Node cloning removes leftover event listeners preventing the bug in tickets #28300 and #28317.
         *
         * See https://stackoverflow.com/questions/9251837/how-to-remove-all-listeners-in-an-element
         */
        var elEditorCancelOld = document.getElementById("akEditorBtnCancel");
        var elEditorCancel    = elEditorCancelOld.cloneNode(true);

        elEditorCancelOld.parentNode.replaceChild(elEditorCancel, elEditorCancelOld);

        elEditorCancel.addEventListener("click", function ()
        {
            // Close the dialog
            if ((typeof akeebabackup.Multidb.modalDialog == "object") && akeebabackup.Multidb.modalDialog.hide)
            {
                akeebabackup.Multidb.modalDialog.hide();
            }
        });

        // Show editor
        akeebabackup.Multidb.modalDialog = new bootstrap.Modal(editor, {
            keyboard: true,
            backdrop: true
        });
        akeebabackup.Multidb.modalDialog.show();

        akeebabackup.System.triggerEvent(editor.querySelector("span"), "focus");
    });

    var elEditIcon       = document.createElement("span");
    elEditIcon.className = "editbutton ak-toggle-button";
    elEditIcon.insertAdjacentHTML("beforeend", "<span class=\"fa fa-edit\"></span>");
    elEditSpan.appendChild(elEditIcon);

    elTdEdit.appendChild(elEditSpan);

    // Database host
    var elTdHost       = document.createElement("td");
    elTdHost.className = "ak_filter_item";

    var elSpanHost         = document.createElement("span");
    elSpanHost.className   = "ak_filter_name ak_dbhost";
    elSpanHost.textContent = def.host;
    elTdHost.appendChild(elSpanHost);

    // Database name
    var elTdDBName       = document.createElement("td");
    elTdDBName.className = "ak_filter_item";

    var elSpanDBName         = document.createElement("span");
    elSpanDBName.className   = "ak_filter_name ak_dbname";
    elSpanDBName.textContent = def.database;
    elTdDBName.appendChild(elSpanDBName);

    elTr.appendChild(elTdDelete);
    elTr.appendChild(elTdEdit);
    elTr.appendChild(elTdHost);
    elTr.appendChild(elTdDBName);
    append_to_here.appendChild(elTr);
};

akeebabackup.Multidb.addNewRecordButton = function (append_to_here)
{
    var root      = Math.uuid();
    var dummyData = {
        host:     "",
        port:     "",
        username: "",
        password: "",
        database: "",
        prefix:   ""
    };
    akeebabackup.Multidb.addRow(root, dummyData, append_to_here);

    var trList = document.getElementById("ak_list_contents").children;
    var lastTr = trList[trList.length - 1];

    var tdList = lastTr.querySelectorAll("td");

    tdList[0].querySelector("span").style.display = "none";

    var spanList = tdList[1].querySelectorAll("span");
    var elPencil = spanList[spanList.length - 1];


    elPencil.className = "fa fa-plus-circle";
};

akeebabackup.System.documentReady(function ()
{
    akeebabackup.Multidb.render(Joomla.getOptions("akeebabackup.Multidb.guiData", {}));
});