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

if (typeof akeebabackup.Dbfilters == "undefined")
{
    akeebabackup.Dbfilters = {
        currentRoot: null
    }
}

akeebabackup.Dbfilters.activeRootChanged = function ()
{
    var elRoot = document.getElementById("active_root");
    var data   = {
        root: elRoot.options[elRoot.selectedIndex].value
    };
    akeebabackup.Dbfilters.load(data);
};

akeebabackup.Dbfilters.activeTabRootChanged = function ()
{
    var elRoot = document.getElementById("active_root");
    akeebabackup.Dbfilters.loadTab(elRoot.options[elRoot.selectedIndex].value);
};

/**
 * Loads the contents of a database
 *
 * @param data
 */
akeebabackup.Dbfilters.load = function (data)
{
    // Add the verb to the data
    data.verb = "list";

    // Assemble the data array and send the AJAX request
    var new_data = {
        action: JSON.stringify(data)
    };

    akeebabackup.System.doAjax(new_data, function (response)
    {
        akeebabackup.Dbfilters.render(response);
    }, null, false, 15000);
};

/**
 * Toggles a database filter
 * @param data
 * @param caller
 * @param callback
 */
akeebabackup.Dbfilters.toggle = function (data, caller, callback)
{
    akeebabackup.Fsfilters.toggle(data, caller, callback);
};

/**
 * Renders the Database Filters page
 * @param data
 * @return
 */
akeebabackup.Dbfilters.render = function (data)
{
    akeebabackup.Dbfilters.currentRoot = data.root;

    // ----- Render the tables
    var aktables       = document.getElementById("tables");
    aktables.innerHTML = "";

    for (var table in data.tables)
    {
        if (!data.tables.hasOwnProperty(table))
        {
            continue;
        }

        var dbef = data.tables[table];

        var uielement       = document.createElement("div");
        uielement.className = "table-container d-flex my-1 py-1 border-bottom";

        var available_filters = ["tables", "tabledata"];

        for (var counter = 0; counter < available_filters.length; counter++)
        {
            var filter = available_filters[counter];

            var ui_icon       = document.createElement("span");
            ui_icon.className = "table-icon-container btn btn-sm btn-light me-1 hasTooltip";
            ui_icon.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_DBFILTER_TYPE_" + filter.toUpperCase()));

            switch (filter)
            {
                case "tables":
                    ui_icon.insertAdjacentHTML(
                        "beforeend",
                        "<span class=\"ak-toggle-button fa fa-ban\"></span>"
                    );
                    break;

                case "tabledata":
                    ui_icon.insertAdjacentHTML("beforeend", "<span class=\"ak-toggle-button fa fa-database\"></span>");
                    break;
            }

            switch (dbef[filter])
            {
                case 2:
                    ui_icon.classList.remove('btn-light');
                    ui_icon.classList.add('btn-danger');
                    break;

                case 1:
                    ui_icon.classList.remove('btn-light');
                    ui_icon.classList.add('btn-warning');
                // Don't break; we have to add the handler!

                case 0:
                    ui_icon.addEventListener("click", function (ui_icon, table, filter)
                    {
                        return function ()
                        {
                            var new_data = {
                                root:   data.root,
                                node:   table,
                                filter: filter,
                                verb:   "toggle"
                            };
                            akeebabackup.Dbfilters.toggle(new_data, ui_icon);
                        };
                    }(ui_icon, table, filter))
            }

            uielement.appendChild(ui_icon);
        }


        // Add the table label
        var iconclass = "akion-link";
        var icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_MISC";

        switch (dbef.type)
        {
            case "table":
                iconclass = "fa fa-table";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_TABLE";
                break;
            case "view":
                iconclass = "fa fa-th-list";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_VIEW";
                break;
            case "procedure":
                iconclass = "fa fa-cube";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_PROCEDURE";
                break;
            case "function":
                iconclass = "fa fa-code";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_FUNCTION";
                break;
            case "trigger":
                iconclass = "fa fa-bolt";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_TRIGGER";
                break;
            case "temp":
            case "memory":
                iconclass = "fa fa-ghost";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_TEMP";
                break;
            default:
                iconclass = "fa fa-question-circle";
                icontip   = "COM_AKEEBABACKUP_DBFILTER_TABLE_UNKNOWN";
                break;
        }

        var uiTableNameContainer = document.createElement("span");
        var uiTableSizeContainer = document.createElement("span");
        var uiTableType          = document.createElement("span");
        var uiSeparator          = document.createElement("span");

        uiTableNameContainer.className   = "table-name flex-grow-1";
        uiTableNameContainer.textContent = table;

        uiTableSizeContainer.className = "table-rowcount text-muted fst-italic";

        if (dbef.rows)
        {
            uiTableSizeContainer.textContent = dbef.rows;
            uiTableSizeContainer.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_DBFILTER_TABLE_META_ROWCOUNT"));
        }

        uiTableType.className = "table-icon-container table-icon-noclick table-icon-small hasTooltip";
        uiTableType.setAttribute("title", Joomla.Text._(icontip, dbef.type));

        var uiTableTypeIcon       = document.createElement("span");
        uiTableTypeIcon.className = iconclass + ' me-2';
        uiTableType.appendChild(uiTableTypeIcon);

        uiSeparator.className       = "table-icon-container table-icon-noclick table-icon-small";
        var uiSeparatorIcon         = document.createElement("span");
        uiSeparatorIcon.className   = "fa fa-ellipsis-v mx-2";
        uiSeparatorIcon.style.color = "#cccccc";
        uiSeparator.appendChild(uiSeparatorIcon);

        uielement.appendChild(uiSeparator);
        uielement.appendChild(uiTableType);
        uielement.appendChild(uiTableNameContainer);
        uielement.appendChild(uiTableSizeContainer);

        // Render
        aktables.appendChild(uielement);
    }

};

/**
 * Loads the tabular view of the Database Filter for a given root
 * @param root
 * @return
 */
akeebabackup.Dbfilters.loadTab = function (root)
{
    var data = {
        verb: "tab",
        root: root
    };

    // Assemble the data array and send the AJAX request
    var new_data = {
        action: JSON.stringify(data)
    };

    akeebabackup.System.doAjax(new_data, function (response)
    {
        akeebabackup.Dbfilters.renderTab(response);
    }, null, false, 15000);
};

/**
 * Add a row in the tabular view of the Filesystems Filter
 * @param def
 * @param append_to_here
 * @return
 */
akeebabackup.Dbfilters.addRow = function (def, append_to_here)
{
    // Turn def.type into something human readable
    var type_text = Joomla.Text._("COM_AKEEBABACKUP_DBFILTER_TYPE_" + def.type.toUpperCase());

    if (type_text == null)
    {
        type_text = def.type;
    }

    var elRow        = document.createElement("tr");
    var elFilterType = document.createElement("td");
    var elFilterItem = document.createElement("td");

    elRow.className = "ak_filter_row";

    // Filter title
    elFilterType.className   = "ak_filter_type";
    elFilterType.textContent = type_text;

    // Filter item
    elFilterItem.className = "ak_filter_item";

    // delete button, edit button, filter name
    var elDeleteContainer = document.createElement("span");
    var elEditContainer   = document.createElement("span");
    var elFilterName      = document.createElement("span");

    elDeleteContainer.className = "ak_filter_tab_icon_container btn btn-sm btn-danger me-2";
    elDeleteContainer.addEventListener("click", function ()
    {
        if (def.node == "")
        {
            // An empty filter is normally not saved to the database; it's a new record row which has to be removed...
            var elRemove = this.parentNode.parentNode;
            elRemove.parentNode.removeChild(elRemove);

            return;
        }

        var elRoot   = document.getElementById("active_root");
        var new_data = {
            root:   elRoot.options[elRoot.selectedIndex].value,
            node:   def.node,
            filter: def.type,
            verb:   "remove"
        };

        akeebabackup.Dbfilters.toggle(new_data, this, function (response, caller)
        {
            if (response.success)
            {
                var elRemove = caller.parentNode.parentNode;
                elRemove.parentNode.removeChild(elRemove);
            }
        });
    });

    var elDeleteIcon       = document.createElement("span");
    elDeleteIcon.className = "ak-toggle-button icon-trash deletebutton";
    elDeleteContainer.appendChild(elDeleteIcon);

    elEditContainer.className = "ak_filter_tab_icon_container btn btn-sm btn-primary me-2";
    elEditContainer.addEventListener("click", function ()
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

        var elInput = document.createElement("input");
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
            var elRoot   = document.getElementById("active_root");
            var new_data = {
                root:     elRoot.options[elRoot.selectedIndex].value,
                old_node: def.node,
                new_node: new_value,
                filter:   def.type,
                verb:     "swap"
            };

            var elEditContainer = that.parentNode.querySelector("span.editcontainer");

            akeebabackup.Dbfilters.toggle(
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

    var elEditIcon           = document.createElement("span");
    elEditIcon.className = "ak-toggle-button fa fa-edit editbutton";
    elEditContainer.appendChild(elEditIcon);

    elFilterName.className   = "ak_filter_name";
    elFilterName.textContent = def.node;

    elFilterItem.appendChild(elDeleteContainer);
    elFilterItem.appendChild(elEditContainer);
    elFilterItem.appendChild(elFilterName);

    elRow.appendChild(elFilterType);
    elRow.appendChild(elFilterItem);

    append_to_here.appendChild(elRow);
};

akeebabackup.Dbfilters.addNew = function (filtertype)
{
    // Add a row below ourselves
    var new_def = {
        type: filtertype,
        node: ""
    };
    akeebabackup.Dbfilters.addRow(new_def, document.getElementById("ak_list_table").children[1]);

    var trList = document.getElementById("ak_list_table").children[1].children;
    var lastTr = trList[trList.length - 1];
    akeebabackup.System.triggerEvent(lastTr.querySelector("span.editbutton"), "click");
};

/**
 * Renders the tabular view of the Database Filter
 * @param data
 * @return
 */
akeebabackup.Dbfilters.renderTab = function (data)
{
    var tbody       = document.getElementById("ak_list_contents");
    tbody.innerHTML = "";

    if (data?.list?.length)
    {
        data.list.forEach(def => akeebabackup.Dbfilters.addRow(def, tbody));
    }
};

/**
 * Activates the exclusion filters for non-CMS tables
 */
akeebabackup.Dbfilters.excludeNonCMS = function ()
{
    var tables = document.getElementById("tables").children;

    for (var counter = 0; counter < tables.length; counter++)
    {
        var element = tables[counter];

        // Get the table name
        var tablename = element.querySelector("span.table-name").textContent;
        var prefix    = tablename.substr(0, 3);

        // If the prefix is not #__ it's a core table and I have to exclude it
        if (prefix !== "#__")
        {
            var iconContainer = element.querySelector("span.table-icon-container");
            var icon          = iconContainer.querySelector("span.ak-toggle-button");

            if (!iconContainer.classList.contains('btn-warning') && !iconContainer.classList.contains('btn-danger'))
            {
                akeebabackup.System.triggerEvent(icon, 'click');
            }
        }
    }
};

/**
 * Wipes out the database filters
 * @return
 */
akeebabackup.Dbfilters.nuke = function ()
{
    var data     = {
        root: akeebabackup.Dbfilters.currentRoot,
        verb: "reset"
    };
    var new_data = {
        action: JSON.stringify(data)
    };
    akeebabackup.System.doAjax(new_data, function (response)
    {
        akeebabackup.Dbfilters.render(response);
    }, null, false, 15000);
};

akeebabackup.System.documentReady(function ()
{
    var guiData      = Joomla.getOptions("akeebabackup.Databasefilters.guiData", null);
    var viewType     = Joomla.getOptions("akeebabackup.Databasefilters.viewType", null);
    var elActiveRoot = document.getElementById("active_root");

    switch (viewType)
    {
        // ========== LIST VIEW (DEFAULT) ==========
        case "list":
            elActiveRoot.addEventListener(
                "change", akeebabackup.Dbfilters.activeRootChanged
            );
            akeebabackup.Dbfilters.render(guiData);

            document.getElementById("comAkeebaDatabasefiltersExcludeNonCMS")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Dbfilters.excludeNonCMS();

                        return false;
                    });

            document.getElementById("comAkeebaDatabasefiltersNuke")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Dbfilters.nuke();

                        return false;
                    });
            break;

        // ========== TABULAR VIEW ==========
        case "tabular":
            elActiveRoot.addEventListener("change", akeebabackup.Dbfilters.activeTabRootChanged);
            akeebabackup.Dbfilters.renderTab(guiData);

            document.getElementById("comAkeebaDatabasefiltersAddNewTables")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Dbfilters.addNew("tables");

                        return false;
                    });

            document.getElementById("comAkeebaDatabasefiltersAddNewTableData")
                    .addEventListener("click", function ()
                    {
                        akeebabackup.Dbfilters.addNew("tabledata");

                        return false;
                    });
            break;
    }

    akeebabackup.Fsfilters.initTooltips();
});