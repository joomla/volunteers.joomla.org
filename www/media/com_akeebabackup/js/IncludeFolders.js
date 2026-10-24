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

if (typeof akeebabackup.Extradirs == "undefined")
{
    akeebabackup.Extradirs = {};
}

akeebabackup.Extradirs.render = function (data)
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

        akeebabackup.Extradirs.addRow(rootname, def, tbody);
    }

    akeebabackup.Extradirs.addNewRecordButton(tbody);
};

akeebabackup.Extradirs.addRow = function (rootuuid, def, append_to_here)
{
    var elTr       = document.createElement("tr");
    elTr.className = "ak_filter_row";

    // Cache UUID of this entry
    elTr.dataset["rootuuid"] = rootuuid;
    // Cache the definition data (virtual directory)
    elTr.dataset["def"]      = JSON.stringify(def);

    var elDeleteContainer = document.createElement("td");
    var elEditContainer   = document.createElement("td");
    var elDirPath         = document.createElement("td");
    var elVirtualPath     = document.createElement("td");

    elDeleteContainer.style.width = "4em";
    elEditContainer.style.width = "7em";

    // Delete button
    var elDeleteSpan       = document.createElement("span");
    elDeleteSpan.className = "ak_filter_tab_icon_container btn btn-danger btn-sm delete me-2";
    elDeleteSpan.addEventListener("click", function ()
    {
        var new_data = {
            uuid: this.parentNode.parentNode.dataset["rootuuid"],
            verb: "remove"
        };

        akeebabackup.Fsfilters.toggle(new_data, this, function (response, caller)
        {
            if (response.success == true)
            {
                var elRemove = caller.parentNode.parentNode;
                elRemove.parentNode.removeChild(elRemove);
            }
        });
    });

    elDeleteSpan.insertAdjacentHTML("beforeend", "<span class=\"fa fa-trash deletebutton ak-toggle-button\"></span>");
    elDeleteContainer.appendChild(elDeleteSpan);

    // Edit button
    var elEditSpan       = document.createElement("span");
    elEditSpan.className = "ak_filter_tab_icon_container btn btn-primary btn-sm me-2";

    elEditSpan.addEventListener("click", function ()
    {
        // Get reference to data root
        var data_root = this.parentNode.parentNode;

        // Hide pencil icon
        this.style.display = "none";

        // Hide delete icon
        data_root.querySelector("span.delete").style.display = "none";

        var elTd = this.parentNode;
        var elTr = elTd.parentNode;

        // Add a disk icon (save)
        var elDiskIcon       = document.createElement("span");
        elDiskIcon.className =
            "ak_filter_tab_icon_container btn btn-primary btn-sm save ak-toggle-button ak-stacked-button me-2";
        elDiskIcon.insertAdjacentHTML("beforeend", "<span class=\"fa fa-check-circle\"></span>");

        elDiskIcon.addEventListener("click", function ()
        {
            var that = this;

            var new_directory = data_root.querySelector("input.folder_editor").value;
            new_directory     = new_directory.trim();

            var add_dir = data_root.querySelector("input.virtual_editor").value;
            add_dir     = add_dir.trim();

            if (!add_dir.length)
            {
                add_dir = Math.uuid(8) + "-" + new_directory.split(/[\\/]/).pop();
            }

            var old_data = JSON.parse(data_root.dataset["def"] ?? "{}");

            if (new_directory == "")
            {
                if (old_data[0] == "")
                {
                    // Tried to save empty data on new row. That's like Cancel...
                    akeebabackup.System.triggerEvent(that.parentNode.querySelector("span.cancel"), "click");
                }
                else
                {
                    // Tried to save empty data on existing row. That's like Delete...
                    var elDelete           = data_root.querySelector("span.delete");
                    elDelete.style.display = "inline-block";
                    akeebabackup.System.triggerEvent(elDelete, "click");
                }
            }
            else
            {
                // Save entry
                var new_data = {
                    uuid: data_root.dataset["rootuuid"],
                    root: new_directory,
                    data: add_dir,
                    verb: "set"
                };

                akeebabackup.Fsfilters.toggle(new_data, that, function (response, caller)
                {
                    if (response.success == true)
                    {
                        // Catch case of new row
                        if (old_data[0] == "")
                        {
                            // Change icon to pencil
                            var elIcon = caller.parentNode.querySelector("span.editbutton");
                            elIcon.classList.remove("fa-plus-square");
                            elIcon.classList.add("fa", "fa-edit", "ak-toggle-button");

                            // Add new row
                            akeebabackup.Extradirs.addNewRecordButton(append_to_here);
                        }

                        // Update cached data
                        var new_cache_data       = [new_directory, add_dir];
                        data_root.dataset["def"] = JSON.stringify(new_cache_data);

                        // Update values in table
                        data_root.querySelector("span.ak_directory").textContent = new_directory;
                        data_root.querySelector("span.ak_virtual").textContent   = add_dir;

                        // Show pencil icon
                        caller.parentNode.querySelector(
                            "span.ak_filter_tab_icon_container").style.display = "inline-block";

                        // Remove cancel icon
                        var elRemove = caller.parentNode.querySelector("span.cancel");
                        elRemove.parentNode.removeChild(elRemove);

                        // Show the delete button
                        data_root.querySelector("span.delete").style.display = "inline-block";

                        // Remove disk icon
                        caller.parentNode.removeChild(caller);

                        // Remove input boxes
                        elRemove = data_root.querySelector("input.folder_editor");
                        elRemove.parentNode.removeChild(elRemove);

                        elRemove = data_root.querySelector("input.virtual_editor");
                        elRemove.parentNode.removeChild(elRemove);

                        // Remove browser button
                        elRemove = data_root.querySelector("button.browse");
                        elRemove.parentNode.removeChild(elRemove);

                        // Show values
                        data_root.querySelector("span.ak_directory").style.display = "inline-block";
                        data_root.querySelector("span.ak_virtual").style.display   = "inline-block";
                    }
                }, false);
            }
        });

        elTd.appendChild(elDiskIcon);

        // Add a Cancel icon
        var elCancelIcon       = document.createElement("span");
        elCancelIcon.className = "ak_filter_tab_icon_container btn btn-warning btn-sm cancel ak-toggle-button";
        elCancelIcon.insertAdjacentHTML("beforeend", "<span class=\"fa fa-times-circle \"></span>");

        elCancelIcon.addEventListener("click", function ()
        {
            var that                                                                         = this;
            // Show pencil icon
            that.parentNode.querySelector("span.ak_filter_tab_icon_container").style.display = "inline-block";

            // Remove disk icon
            var elRemove = that.parentNode.querySelector("span.save");
            elRemove.parentNode.removeChild(elRemove);

            // Remove cancel icon
            that.parentNode.removeChild(that);

            // Remove input boxes
            elRemove = data_root.querySelector("input.folder_editor");
            elRemove.parentNode.removeChild(elRemove);

            elRemove = data_root.querySelector("input.virtual_editor");
            elRemove.parentNode.removeChild(elRemove);

            // Remove browser button
            elRemove = data_root.querySelector("button.browse");
            elRemove.parentNode.removeChild(elRemove);

            // Show values
            data_root.querySelector("span.ak_directory").style.display = "inline-block";
            data_root.querySelector("span.ak_virtual").style.display   = "inline-block";

            // Show the delete button (if it's NOT a new row)
            var old_data = JSON.parse(data_root.dataset["def"] ?? "{}");

            if (old_data[0] != "")
            {
                data_root.querySelector("span.delete").style.display = "inline-block";
            }

        });

        elTd.appendChild(elCancelIcon);

        // Show edit box
        var old_data           = JSON.parse(data_root.dataset["def"] ?? "{}");
        var elFilterContainer  = elTr.querySelector("td.ak_filter_item");
        var elVirtualContainer = elFilterContainer.nextElementSibling;

        // -- Show input element for the filter (folder to include)
        var elFilterInput = document.createElement("input");
        elFilterInput.setAttribute("type", "text");
        elFilterInput.className = "folder_editor form-control";
        elFilterInput.value     = old_data[0];

        // -- Show browser button
        var elBrowser       = document.createElement("button");
        elBrowser.setAttribute('type', 'button');
        elBrowser.className = "ak_filter_tab_icon_container btn btn-dark btn-sm browse ak-toggle-button";
        elBrowser.insertAdjacentHTML("beforeend", "<span class=\"fa fa-folder-open\"></span>");

        elBrowser.addEventListener("click", function ()
        {
            var that   = this;
            // Show folder open dialog
            var editor = that.parentNode.querySelector("input.folder_editor");
            var val    = editor.value.trim();

            if (val == "")
            {
                val = "[ROOTPARENT]";
            }

            akeebabackup.Configuration.onBrowser(val, editor);
        });

        var elInputGroup = document.createElement('div');
        elInputGroup.className = 'input-group';

        elInputGroup.appendChild(elFilterInput);
        elInputGroup.appendChild(elBrowser);
        elFilterContainer.appendChild(elInputGroup);

        var elVirtualInput = document.createElement("input");

        elVirtualInput.setAttribute("type", "text");
        elVirtualInput.className = "virtual_editor form-control";
        elVirtualInput.value     = old_data[1];

        elVirtualContainer.appendChild(elVirtualInput);

        // Hide existing value boxes
        elFilterContainer.querySelector("span.ak_directory").style.display = "none";
        elVirtualContainer.querySelector("span.ak_virtual").style.display  = "none";
    });

    elEditSpan.insertAdjacentHTML("beforeend", "<span class=\"fa fa-edit editbutton ak-toggle-button\"></span>");
    elEditContainer.appendChild(elEditSpan);

    // Directory path
    elDirPath.className          = "ak_filter_item";
    var elFilterNameSpan         = document.createElement("span");
    elFilterNameSpan.className   = "ak_filter_name ak_directory";
    elFilterNameSpan.textContent = def[0];
    elDirPath.appendChild(elFilterNameSpan);

    // Virtual path
    elVirtualPath.className       = "ak_filter_item";
    var elVirtualNameSpan         = document.createElement("span");
    elVirtualNameSpan.className   = "ak_filter_name ak_virtual";
    elVirtualNameSpan.textContent = def[1];
    elVirtualPath.appendChild(elVirtualNameSpan);

    elTr.appendChild(elDeleteContainer);
    elTr.appendChild(elEditContainer);
    elTr.appendChild(elDirPath);
    elTr.appendChild(elVirtualPath);

    append_to_here.appendChild(elTr);
};

akeebabackup.Extradirs.addNewRecordButton = function (append_to_here)
{
    var newUUID   = Math.uuid();
    var dummyData = ["", ""];
    akeebabackup.Extradirs.addRow(newUUID, dummyData, append_to_here);

    var trList = document.getElementById("ak_list_contents").children;
    var lastTr = trList[trList.length - 1];

    var tdList = lastTr.querySelectorAll("td");

    tdList[0].querySelector("span").style.display = "none";

    var spanList = tdList[1].querySelectorAll("span");
    var elPencil = spanList[spanList.length - 1];
    elPencil.classList.remove("fa-edit");
    elPencil.classList.add("fa-plus-square", "ak-toggle-button");
};

akeebabackup.System.documentReady(function ()
{
    var guiData = Joomla.getOptions("akeebabackup.Includefolders.guiData", null);

    if (guiData === null)
    {
        return;
    }

    akeebabackup.Configuration.initialisePopovers();
    akeebabackup.Extradirs.render(guiData);
});
