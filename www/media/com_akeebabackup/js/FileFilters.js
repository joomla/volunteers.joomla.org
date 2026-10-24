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

if (typeof akeebabackup.Fsfilters == "undefined")
{
    akeebabackup.Fsfilters = {
        currentRoot: null
    }
}

akeebabackup.Fsfilters.activeRootChanged = function ()
{
    var elRoot  = document.getElementById("active_root");
    var data    = {};
    data.root   = elRoot.options[elRoot.selectedIndex].value;
    data.crumbs = [];
    data.node   = "";
    akeebabackup.Fsfilters.load(data);
};

akeebabackup.Fsfilters.activeTabRootChanged = function ()
{
    var elRoot = document.getElementById("active_root");
    akeebabackup.Fsfilters.loadTab(elRoot.options[elRoot.selectedIndex].value);
};

/**
 * Loads the contents of a directory
 *
 * @param  data
 */
akeebabackup.Fsfilters.load = function (data)
{
    // Add the verb to the data
    data.verb = "list";

    // Convert to JSON
    var json = JSON.stringify(data);

    // Assemble the data array and send the AJAX request
    var new_data    = {};
    new_data.action = json;

    akeebabackup.System.doAjax(new_data, function (response)
    {
        akeebabackup.Fsfilters.render(response);
    }, null, false, 15000);
};

/**
 * Toggles a filesystem filter
 */
akeebabackup.Fsfilters.toggle = function (data, caller, callback, use_inner_child)
{
    if (use_inner_child == null)
    {
        use_inner_child = true;
    }

    // Make the icon spin
    if (caller != null)
    {
        // Do not allow multiple simultaneous AJAX requests on the same object
        if ((caller.dataset["loading"] ?? "0") == "1")
        {
            return;
        }

        caller.dataset["loading"] = "1";
        var icon_span             = caller;

        if (use_inner_child)
        {
            icon_span = caller.querySelector("span");
        }

        var existingIconClass = caller.dataset["iconClass"] ?? "";

        if (!existingIconClass)
        {
            caller.dataset["iconClass"] = icon_span.className;
        }

        icon_span.className = "ak-toggle-button ak-toggle-button-spinning akeebabackup-rotate fa fa-sync-alt";
    }

    // Convert to JSON
    var json     = JSON.stringify(data);
    // Assemble the data array and send the AJAX request
    var new_data = {
        action: json
    };

    akeebabackup.System.doAjax(new_data, function (response)
    {
        if (caller != null)
        {
            var storedClassName = caller.dataset["iconClass"] ?? null;

            if (storedClassName)
            {
                icon_span.className = storedClassName;
            }

            if ("iconClass" in caller.dataset)
            {
                delete caller.dataset.iconClass;
            }

            if ("loading" in caller.dataset)
            {
                delete caller.dataset.loading;
            }
        }

        if (response.success === true)
        {
            if (caller != null)
            {
                if (use_inner_child)
                {
                    // Update the on-screen filter state
                    if (response.newstate == true)
                    {
                        caller.classList.remove("btn-light");
                        caller.classList.add("btn-warning");
                    }
                    else
                    {
                        caller.classList.remove("btn-warning");
                        caller.classList.add("btn-light");
                    }
                }
            }

            if (!(callback == null))
            {
                callback(response, caller);
            }
        }
        else
        {
            if (!(callback == null))
            {
                callback(response, caller);
            }

            akeebabackup.System.modalErrorHandler(
                Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIERRORFILTER").replace("%s", data.node));
        }
    }, function (msg)
    {
        // Error handler
        if (caller != null)
        {
            icon_span.className = caller.dataset["iconClass"] ?? null;

            if ("iconClass" in caller.dataset)
            {
                delete caller.dataset.iconClass;
            }

            if ("loading" in caller.dataset)
            {
                delete caller.dataset.loading;
            }
        }

        akeebabackup.System.params.errorCallback(msg);
    }, true, 15000);
};

/**
 * Renders the Filesystem Filters page
 * @param data
 * @return
 */
akeebabackup.Fsfilters.render = function (data)
{
    akeebabackup.Fsfilters.currentRoot = data.root;

    // ----- Render the crumbs bar
    var crumbs = akeebabackup.Fsfilters.renderCrumbs(data);

    // ----- Render the subdirectories
    var akfolders       = document.getElementById("folders");
    akfolders.innerHTML = "";

    if (data.crumbs.length > 0)
    {
        akeebabackup.Fsfilters.renderParentFolderElement();
    }

    // Append the "Apply to all" buttons
    if (Object.keys(data.folders).length > 0)
    {
        var headerFilters    = ["directories_all", "skipdirs_all", "skipfiles_all"];
        var headerDirs       = document.createElement("div");
        headerDirs.className = "folder-header folder-container";

        for (var index = 0; index < headerFilters.length; index++)
        {
            var filter = headerFilters[index];

            ui_icon           = document.createElement("span");
            ui_icon.className = "folder-icon-container btn btn-sm btn-dark me-1 hasTooltip";
            ui_icon.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + filter.toUpperCase()));

            var applyTo = "";

            switch (filter)
            {
                case "directories_all":
                    applyTo = "fa-ban";
                    ui_icon.insertAdjacentHTML(
                        "beforeend",
                        "<span class=\"ak-toggle-button fa fa-ban\"></span>"
                    );
                    break;
                case "skipdirs_all":
                    applyTo = "fa-folder";
                    ui_icon.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-folder\"></span>");
                    break;
                case "skipfiles_all":
                    applyTo = "fa-file";
                    ui_icon.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-file\"></span>");
                    break;
            }

            ui_icon.addEventListener("click", function (ui_icon, applyTo)
            {
                return function ()
                {
                    var selected;

                    if (ui_icon.classList.contains("btn-warning"))
                    {
                        ui_icon.classList.remove("btn-warning");
                        ui_icon.classList.add("btn-dark");
                        selected = false;
                    }
                    else
                    {
                        ui_icon.classList.remove("btn-dark");
                        ui_icon.classList.add("btn-warning");
                        selected = true;
                    }

                    // Start iterating from the second element (the first is the header, we want to skip it!)
                    for (var j = 1; j < akfolders.children.length; j++)
                    {
                        var folderElement = akfolders.children[j];
                        var item          = folderElement.querySelector("span." + applyTo);

                        var hasClass = item.parentNode.classList.contains("btn-warning");

                        // I have to exclude items that have the same state of the desired one, otherwise I'll toggle it
                        if ((!selected && !hasClass) || (selected && hasClass))
                        {
                            continue;
                        }

                        akeebabackup.System.triggerEvent(item, "click");
                    }
                }
            }(ui_icon, applyTo));

            headerDirs.appendChild(ui_icon);
        }

        var elButton       = document.createElement("span");
        elButton.className = "folder-name fst-italic";
        elButton.innerHTML = "<span class=\"pull-left fa fa-arrow-down px-1\"></span>" +
            Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_APPLYTOALLDIRS");
        headerDirs.appendChild(elButton);
        akfolders.appendChild(headerDirs);
    }

    for (var folder in data.folders)
    {
        if (!data.folders.hasOwnProperty(folder))
        {
            return;
        }

        var def = data.folders[folder];

        var uielement       = document.createElement("div");
        uielement.className = "folder-container d-flex my-1 py-1 border-top";

        available_filters = ["directories", "skipdirs", "skipfiles"];

        for (var ctFilter = 0; ctFilter < available_filters.length; ctFilter++)
        {
            filter = available_filters[ctFilter];

            ui_icon           = document.createElement("span");
            ui_icon.className = "btn btn-sm btn-light me-1 hasTooltip folder-icon-container";
            ui_icon.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + filter.toUpperCase()));

            switch (filter)
            {
                case "directories":
                    ui_icon.insertAdjacentHTML(
                        "beforeend",
                        "<span class=\"ak-toggle-button fa fa-ban\"></span>"
                    );
                    break;
                case "skipdirs":
                    ui_icon.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-folder\"></span>");
                    break;
                case "skipfiles":
                    ui_icon.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-file\"></span>");
                    break;
            }

            switch (def[filter])
            {
                case 2:
                    ui_icon.classList.remove("btn-light");
                    ui_icon.classList.add("btn-danger");
                    break;

                case 1:
                    ui_icon.classList.remove("btn-light");
                    ui_icon.classList.add("btn-warning");

                // Don't break; we have to add the handler!

                case 0:
                    ui_icon.addEventListener("click", function (folder, filter, ui_icon)
                    {
                        return function ()
                        {
                            var new_data = {
                                root:   data.root,
                                crumbs: crumbs,
                                node:   folder,
                                filter: filter,
                                verb:   "toggle"
                            };

                            akeebabackup.Fsfilters.toggle(new_data, ui_icon);
                        }
                    }(folder, filter, ui_icon));
            }

            uielement.appendChild(ui_icon);
        }

        // Add the folder label and make clicking on it load its listing
        var elFolderName         = document.createElement("span");
        elFolderName.textContent = folder;

        if (def["link"])
        {
            elFolderName.innerHTML =
                "<span class=\"fa fa-link\" aria-hidden=\"true\"></span> " + elFolderName.innerHTML;
        }

        elFolderName.className = "folder-name";
        elFolderName.addEventListener("click", function (folder)
        {
            return function ()
            {
                // Show "loading" animation
                var elImg = document.createElement("img");
                elImg.setAttribute("src", Joomla.getOptions("akeebabackup.Fsfilters.loadingGif", ""));
                elImg.setAttribute("width", 16);
                elImg.setAttribute("height", 11);
                elImg.setAttribute("border", 0);
                elImg.setAttribute("alt", "Loading...");
                elImg.style.marginTop  = "3px";
                elImg.style.marginLeft = "5px";
                this.appendChild(elImg);

                var new_data = {
                    root:   data.root,
                    crumbs: crumbs,
                    node:   folder
                };
                akeebabackup.Fsfilters.load(new_data);
            }
        }(folder));

        uielement.appendChild(elFolderName);
        // Render
        akfolders.appendChild(uielement);
    }

    // ----- Render the files
    var akfiles       = document.getElementById("files");
    akfiles.innerHTML = "";

    // Append the "Apply to all" buttons
    // TODO Implement me
    if (Object.keys(data.files).length > 0)
    {
        var headerFiles       = document.createElement("div");
        headerFiles.className = "file-header file-container";

        var ui_icon       = document.createElement("span");
        ui_icon.className = "file-icon-container btn btn-sm btn-dark me-1 hasTooltip";
        ui_icon.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-ban\"></span>");

        ui_icon.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_FILES_ALL"));

        ui_icon.addEventListener("click", function (ui_icon)
        {
            return function ()
            {
                var selected;

                if (this.classList.contains("btn-warning"))
                {
                    this.classList.remove("btn-warning");
                    this.classList.add("btn-dark");
                    selected = false;
                }
                else
                {
                    this.classList.remove("btn-dark");
                    this.classList.add("btn-warning");
                    selected = true;
                }

                // We iterate from the second element since we don't want to trigger the header (would cause infinite
                // loop)
                var akfiles = document.getElementById("files");
                for (var ctFiles = 1; ctFiles < akfiles.children.length; ctFiles++)
                {
                    var elFile = akfiles.children[ctFiles];
                    var item   = elFile.querySelector("span.fa-ban");

                    var hasClass = item.parentNode.classList.contains("btn-warning");

                    // I have to exclude items that have the same state of the desidered one, otherwise I'll toggle it
                    if ((!selected && !hasClass) || (selected && hasClass))
                    {
                        continue;
                    }

                    akeebabackup.System.triggerEvent(item, "click");
                }
            };
        }(ui_icon));

        headerFiles.appendChild(ui_icon);

        var elFilename       = document.createElement("span");
        elFilename.className = "file-name";
        elFilename.innerHTML = "<span class=\"pull-left akion-arrow-down-a\"></span>" +
            Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_APPLYTOALLFILES");
        headerFiles.appendChild(elFilename);
        akfiles.appendChild(headerFiles);
    }

    for (var fileName in data.files)
    {
        if (!data.files.hasOwnProperty(fileName))
        {
            continue;
        }

        def = data.files[fileName];

        uielement           = document.createElement("div");
        uielement.className = "file-container d-flex my-1 py-1 border-top";

        var available_filters = ["files"];

        for (var ctFileFilter = 0; ctFileFilter < available_filters.length; ctFileFilter++)
        {
            filter = available_filters[ctFileFilter];

            ui_icon           = document.createElement("span");
            ui_icon.className = "file-icon-container btn btn-sm btn-light me-1 hasTooltip";

            switch (filter)
            {
                case "files":
                    ui_icon.insertAdjacentHTML(
                        "beforeend",
                        "<span class=\"ak-toggle-button fa fa-ban\"></span>"
                    );
                    break;
            }

            ui_icon.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + filter.toUpperCase()));

            switch (def[filter])
            {
                case 2:
                    ui_icon.classList.remove("btn-light");
                    ui_icon.classList.add("btn-danger");
                    break;

                case 1:
                    ui_icon.classList.remove("btn-light");
                    ui_icon.classList.add("btn-warning");
                // Don't break; we have to add the handler!

                case 0:
                    ui_icon.addEventListener("click", function (fileName, filter, ui_icon)
                    {
                        return function ()
                        {
                            var new_data = {
                                root:   data.root,
                                crumbs: crumbs,
                                node:   fileName,
                                filter: filter,
                                verb:   "toggle"
                            };
                            akeebabackup.Fsfilters.toggle(new_data, ui_icon);
                        }
                    }(fileName, filter, ui_icon));
            }

            uielement.appendChild(ui_icon);
        }

        // Add the file label
        var elName         = document.createElement("span");
        elName.className   = "file-name flex-grow-1";
        elName.textContent = fileName;

        if (def["link"])
        {
            elName.innerHTML = "<span class=\"akion-link\" aria-hidden=\"true\"></span> " + elName.innerHTML;
        }

        uielement.appendChild(elName);

        var elSize         = document.createElement("span");
        elSize.className   = "file-size text-muted fst-italic";
        elSize.textContent = def["size"];
        uielement.appendChild(elSize);

        // Render
        akfiles.appendChild(uielement);
    }
};

/**
 * Wipes out the filesystem filters
 * @return
 */
akeebabackup.Fsfilters.nuke = function ()
{
    var data     = {
        root: akeebabackup.Fsfilters.currentRoot,
        verb: "reset"
    };
    // Assemble the data array and send the AJAX request
    var new_data = {
        action: JSON.stringify(data)
    };
    akeebabackup.System.doAjax(new_data, function (response)
    {
        akeebabackup.Fsfilters.render(response);
    }, null, false, 15000);
};

/**
 * Loads the tabular view of the Filesystems Filter for a given root
 * @param root
 * @return
 */
akeebabackup.Fsfilters.loadTab = function (root)
{
    var data     = {
        verb: "tab",
        root: root
    };
    // Assemble the data array and send the AJAX request
    var new_data = {
        action: JSON.stringify(data)
    };
    akeebabackup.System.doAjax(new_data, function (response)
    {
        akeebabackup.Fsfilters.renderTab(response);
    }, null, false, 15000);
};

/**
 * Add a row in the tabular view of the Filesystems Filter
 * @param def
 * @param append_to_here
 * @return
 */
akeebabackup.Fsfilters.addRow = function (def, append_to_here)
{
    // Turn def.type into something human readable
    var type_text = Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + def.type.toUpperCase());

    if (type_text == null)
    {
        type_text = def.type;
    }

    var elRow       = document.createElement("tr");
    elRow.className = "ak_filter_row";

    var elFilterTitle = document.createElement("td");
    elRow.appendChild(elFilterTitle);
    elFilterTitle.className = "ak_filter_type";
    elFilterTitle.insertAdjacentHTML("beforeend", type_text);

    var elIcons = document.createElement("td");
    elRow.appendChild(elIcons);
    elIcons.className = "ak_filter_item";

    var elDeleteButton = document.createElement("span");
    elIcons.appendChild(elDeleteButton);
    elDeleteButton.className = "ak_filter_tab_icon_container btn btn-sm btn-danger me-2 deletecontainer";
    elDeleteButton.addEventListener("click", function ()
    {
        if (def.node == "")
        {
            // An empty filter is normally not saved to the database; it's a new record row which has to be removed...
            var elRemove = this.parentNode.parentNode;
            elRemove.parentNode.removeChild(elRemove);

            return;
        }

        var activeRoot = document.getElementById("active_root");

        var new_data = {
            root:   activeRoot.options[activeRoot.selectedIndex].value,
            crumbs: [],
            node:   def.node,
            filter: def.type,
            verb:   "toggle"
        };

        akeebabackup.Fsfilters.toggle(new_data, this, function (response, caller)
        {
            if (response.success)
            {
                var elRemove = caller.parentNode.parentNode;
                elRemove.parentNode.removeChild(elRemove);
            }
        });
    });
    elDeleteButton.insertAdjacentHTML(
        "beforeend",
        "<span class=\"ak-toggle-button fa fa-trash deletebutton\"></span>"
    );

    var elEditButton = document.createElement("span");
    elIcons.appendChild(elEditButton);
    elEditButton.className = "ak_filter_tab_icon_container btn btn-sm btn-primary me-1 editcontainer";
    elEditButton.addEventListener("click", function ()
    {
        // If I'm editing there's an input box appended to the parent element of this edit button
        var inputBox = this.parentNode.querySelector("input");

        // So, if I'm already editing quit; we mustn't show multiple edit boxes!
        if (inputBox != null)
        {
            return;
        }

        // Hide the text label
        this.parentNode.querySelector("span.ak_filter_name").style.display = "none";

        var elInput       = document.createElement("input");
        elInput.className = "form-input";
        elInput.setAttribute("type", "text");
        elInput.setAttribute("size", 60);
        elInput.value = this.parentNode.querySelector("span.ak_filter_name").textContent;
        this.parentNode.appendChild(elInput);
        elInput.addEventListener("blur", function ()
        {
            var new_value = this.value;
            var that      = this;

            if (new_value == "")
            {
                // Well, if the user meant to remove the filter, let's help him!
                akeebabackup.System.triggerEvent(that.parentNode.querySelector("span.deletebutton"), "click");

                return;
            }

            // First, remove the old filter
            var elRoot = document.getElementById("active_root");

            var new_data = {
                root:     elRoot.options[elRoot.selectedIndex].value,
                crumbs:   [],
                old_node: def.node,
                new_node: new_value,
                filter:   def.type,
                verb:     "swap"
            };

            var elEditContainer = that.parentNode.querySelector("span.editcontainer");

            akeebabackup.Fsfilters.toggle(
                new_data,
                elEditContainer,
                function (response, caller)
                {
                    // Remove the editor
                    var elFilterName           = that.parentNode.querySelector("span.ak_filter_name");
                    elFilterName.style.display = "inline-block";
                    elFilterName.textContent   = new_value;

                    that.parentNode.removeChild(that);
                    def.node = new_value;
                }
            );
        });
        elInput.focus();
    });
    elEditButton.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-edit editbutton\"></span>");

    var elFilterName         = document.createElement("span");
    elFilterName.className   = "ak_filter_name";
    elFilterName.textContent = def.node;
    elIcons.appendChild(elFilterName);

    append_to_here.appendChild(elRow);
};

akeebabackup.Fsfilters.addNew = function (filtertype)
{
    // Add a row below ourselves
    var new_def = {
        type: filtertype,
        node: ""
    };
    akeebabackup.Fsfilters.addRow(new_def, document.getElementById("ak_list_table").children[1]);

    var trList = document.getElementById("ak_list_table").children[1].children;
    var lastTr = trList[trList.length - 1];
    akeebabackup.System.triggerEvent(lastTr.querySelector("span.editcontainer"), "click");
};

/**
 * Renders the tabular view of the Filesystems Filter
 * @param data
 * @return
 */
akeebabackup.Fsfilters.renderTab = function (data)
{
    var tbody       = document.getElementById("ak_list_contents");
    tbody.innerHTML = "";

    if (data?.list?.length)
    {
        data.list.forEach(def => akeebabackup.Fsfilters.addRow(def, tbody));
    }
};

akeebabackup.Fsfilters.renderCrumbs = function (data)
{
    // Create a new crumbs data array
    var crumbsdata = [];
    // Push the "navigate to root" element
    var newCrumb   = [
        Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIROOT"), // [0] : UI Label
        data.root,                     // [1] : Root node
        [],                            // [2] : Crumbs to current directory
        ""                             // [3] : Node element
    ];

    crumbsdata.push(newCrumb);
    var counter = 0;

    // Iterate existing crumbs
    if (data.crumbs.length > 0)
    {
        var crumbs = [];

        for (counter = 0; counter < data.crumbs.length; counter++)
        {
            var crumb = data.crumbs[counter];

            newCrumb = [
                crumb,
                data.root,
                crumbs.slice(0), // Otherwise it is copied by reference
                crumb
            ];

            crumbsdata.push(newCrumb);
            crumbs.push(crumb); // Push this dir into the crumb list
        }
    }

    // Render the UI crumbs elements
    var akcrumbs       = document.getElementById("ak_crumbs");
    akcrumbs.innerHTML = "";

    var def = null;

    for (counter = 0; counter < crumbsdata.length; counter++)
    {
        def        = crumbsdata[counter];
        var myLi   = document.createElement("li");
        var elLink = document.createElement("a");

        myLi.className = "breadcrumb-item";

        if (crumbsdata.length - counter === 1)
        {
            myLi.className = myLi.className + " active";
        }

        def[0]             = def[0].replace("&lt;", "<").replace("&gt;", ">");
        elLink.textContent = def[0];
        elLink.setAttribute("type", "button");
        elLink.addEventListener("click", function (def)
        {
            return function ()
            {
                var elImg = document.createElement("img");
                elImg.setAttribute("src", Joomla.getOptions("akeebabackup.Fsfilters.loadingGif", ""));
                elImg.setAttribute("width", 16);
                elImg.setAttribute("height", 11);
                elImg.setAttribute("border", 0);
                elImg.setAttribute("alt", "Loading...");
                elImg.className = "p-3";
                this.appendChild(elImg);

                var new_data = {
                    root:   def[1],
                    crumbs: def[2],
                    node:   def[3]
                };

                akeebabackup.Fsfilters.load(new_data);
            }
        }(def));

        myLi.appendChild(elLink);

        akcrumbs.appendChild(myLi);
    }

    return crumbs;
};

akeebabackup.Fsfilters.renderParentFolderElement = function ()
{
    var akfolders = document.getElementById("folders");

    // The parent directory element
    var uielement       = document.createElement("div");
    uielement.className = "folder-container folder-header";
    uielement.insertAdjacentHTML("beforeend", "<span class=\"folder-padding\"></span>");
    uielement.insertAdjacentHTML("beforeend", "<span class=\"folder-padding\"></span>");
    uielement.insertAdjacentHTML("beforeend", "<span class=\"folder-padding\"></span>");

    uielement.insertAdjacentHTML("beforeend", "<span class=\"akion-arrow-up-a\"></span>");

    var elFolderUp       = document.createElement("span");
    elFolderUp.className = "folder-name folder-up";

    var elCrumbs           = document.getElementById("ak_crumbs").children;
    var elParentFolder     = elCrumbs[elCrumbs.length - 2].querySelector("a");
    elFolderUp.textContent = " (" + elParentFolder.textContent + ")";

    elFolderUp.addEventListener("click", function (elParentFolder)
    {
        return function ()
        {
            akeebabackup.System.triggerEvent(elParentFolder, "click");
        }
    }(elParentFolder));

    uielement.appendChild(elFolderUp);
    akfolders.appendChild(uielement);
};

akeebabackup.Fsfilters.initTooltips = function ()
{
    var tooltips = Joomla.getOptions("bootstrap.tooltip");

    if (typeof tooltips !== "object" || tooltips === null)
    {
        return;
    }

    Object.keys(tooltips).forEach(function (tooltip)
    {
        var options  = tooltips[tooltip];
        var elements = Array.from(document.querySelectorAll(tooltip));
        if (!elements.length)
        {
            return;
        }

        elements.map(function (el)
        {
            new window.bootstrap.Tooltip(el, options)
        });
    });
}

akeebabackup.System.documentReady(function ()
{
    // This file may be included in the other views. In this case do NOT run our GUI initialization.
    var guiData      = Joomla.getOptions("akeebabackup.Filefilters.guiData", null);
    var viewType     = Joomla.getOptions("akeebabackup.Filefilters.viewType", null);
    var elActiveRoot = document.getElementById("active_root");

    if (guiData === null)
    {
        return;
    }

    switch (viewType)
    {
        // ========== LIST VIEW (DEFAULT) ==========
        case "list":
            akeebabackup.Fsfilters.render(guiData);
            elActiveRoot.addEventListener("change", akeebabackup.Fsfilters.activeRootChanged);

            document.getElementById("comAkeebaFilefiltersNuke")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Fsfilters.nuke();

                        return false;
                    });
            break;

        case "tabular":
            akeebabackup.Fsfilters.renderTab(guiData);
            elActiveRoot.addEventListener("change", akeebabackup.Fsfilters.activeTabRootChanged);

            document.getElementById("comAkeebaFilefiltersAddDirectories")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Fsfilters.addNew("directories");

                        return false;
                    });

            document.getElementById("comAkeebaFilefiltersAddSkipfiles")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Fsfilters.addNew("skipfiles");

                        return false;
                    });

            document.getElementById("comAkeebaFilefiltersAddSkipdirs")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Fsfilters.addNew("skipdirs");

                        return false;
                    });

            document.getElementById("comAkeebaFilefiltersAddFiles")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Fsfilters.addNew("files");

                        return false;
                    });
            break;
    }

    akeebabackup.Fsfilters.initTooltips();
});