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

if (typeof akeebabackup.Regexfsfilters == "undefined")
{
    akeebabackup.Regexfsfilters = {
        currentRoot: null
    }
}

/**
 * Change the active root
 */
akeebabackup.Regexfsfilters.activeRootChanged = function ()
{
    var elRoot = document.getElementById("active_root");
    akeebabackup.Regexfsfilters.load(elRoot.options[elRoot.selectedIndex].value);
};

/**
 * Load data from the server
 *
 * @param   new_root  The root to load data for
 */
akeebabackup.Regexfsfilters.load = function load(new_root)
{
    var data = {
        root: new_root,
        verb: "list"
    };

    var request = {
        action: JSON.stringify(data)
    };
    akeebabackup.System.doAjax(request, function (response)
    {

        akeebabackup.Regexfsfilters.render(response);
    }, null, false, 15000);
};

/**
 * Render the data in the GUI
 *
 * @param   data
 */
akeebabackup.Regexfsfilters.render = function (data)
{
    const tbody       = document.getElementById("ak_list_contents");
    tbody.innerHTML = "";

    if (data?.list?.length) {
        data.list.forEach(def => akeebabackup.Regexfsfilters.addRow(def, tbody));
    }

    akeebabackup.Regexfsfilters.addNewRow(tbody);
};

/**
 * Adds a row to the GUI
 *
 * @param   def             Filter definition
 * @param   append_to_here  Element to append the row to
 */
akeebabackup.Regexfsfilters.addRow = function (def, append_to_here)
{
    var trow = document.createElement("tr");
    append_to_here.appendChild(trow);

    // Is this an existing filter or a new one?
    var edit_icon_class = "fa fa-edit";
    if (def.item == "")
    {
        edit_icon_class = "fa fa-plus-square";
    }

    var td_buttons = document.createElement("td");
    var elEdit     = document.createElement("span");
    var elDelete   = document.createElement("span");

    td_buttons.appendChild(elEdit);
    td_buttons.appendChild(elDelete);
    trow.appendChild(td_buttons);

    elEdit.className = "table-icon-container btn btn-primary btn-sm edit ak-toggle-button me-2";
    elEdit.insertAdjacentHTML("beforeend", "<span class=\"" + edit_icon_class + "\"></span>");

    elEdit.addEventListener("click", function ()
    {
        // Create the drop down
        var known_filters = [
            "regexfiles",
            "regexdirectories",
            "regexskipdirs",
            "regexskipfiles"
        ];
        var mySelect      = document.createElement("select");
        mySelect.setAttribute("name", "type");
        mySelect.className = "type-select form-select";

        for (var i = 0; i < known_filters.length; i++)
        {
            var filter_name = known_filters[i];
            var selected    = false;

            if (filter_name === def.type)
            {
                selected = true;
            }

            var elOption = document.createElement("option");

            if (selected)
            {
                elOption.setAttribute("selected", "selected");
            }

            elOption.setAttribute("value", filter_name);
            elOption.textContent =
                Joomla.Text._("COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + String(filter_name).toUpperCase().substr(5));

            mySelect.appendChild(elOption);
        }

        // Switch the type span with the drop-down
        trow.querySelector("td.ak-type span").style.display = "none";
        trow.querySelector("td.ak-type").appendChild(mySelect);

        // Create the edit box
        var myEditBox       = document.createElement("input");
        myEditBox.className = "form-control w-100"
        myEditBox.setAttribute("type", "text");
        myEditBox.setAttribute("name", "item");
        myEditBox.value = def.item;

        // Switch the item code with the input box
        trow.querySelector("td.ak-item code").style.display = "none";
        trow.querySelector("td.ak-item").appendChild(myEditBox);

        // Hide the edit/delete buttons, add save/cancel buttons
        var tdFirst = trow.children[0];

        tdFirst.querySelector("span.edit").style.display   = "none";
        tdFirst.querySelector("span.delete").style.display = "none";

        var elSave = document.createElement("span");

        elSave.className = "table-icon-container btn btn-primary btn-sm save me-2 ak-toggle-button";

        elSave.insertAdjacentHTML("beforeend", "<span class=\"fa fa-save\"></span>");

        elSave.addEventListener("click", function ()
        {
            var tdFirst = trow.children[0];

            tdFirst.querySelector("span.cancel").style.display = "none";

            var new_type = mySelect.options[mySelect.selectedIndex].value;
            var new_item = myEditBox.value;

            if (new_item.trim() === "")
            {
                // Empty item detected. It is equivalent to delete or cancel.
                if (def.item === "")
                {
                    akeebabackup.System.triggerEvent(tdFirst.querySelector("span.cancel"), "click");

                    return;
                }
                else
                {
                    akeebabackup.System.triggerEvent(tdFirst.querySelector("span.delete"), "click");

                    return;
                }
            }

            // If no change is detected we have to cancel, not save
            if ((def.item === new_item) && (def.type === new_type))
            {
                akeebabackup.System.triggerEvent(tdFirst.querySelector("span.cancel"), "click");

                return;
            }

            var elRoot   = document.getElementById("active_root");
            var new_data = {
                verb: "set",
                type: new_type,
                node: new_item,
                root: elRoot.options[elRoot.selectedIndex].value
            };

            akeebabackup.Fsfilters.toggle(new_data, this, function (response, caller)
            {
                // Now that we saved the new filter, delete the old one
                var haveToDelete = (def.item != "") && (def.type != "") &&
                    ((def.item != new_item) || (def.type != new_type));
                var addedNewItem = (def.item == "") || (def.type == "");
                var tdFirst      = trow.children[0];

                if (def.item == "")
                {
                    var elEdit = tdFirst.querySelector("span.edit").firstChild;

                    elEdit.className = elEdit.className.replace("fa-plus-square", "fa-edit");
                }

                new_data.type = def.type;
                new_data.node = def.item;
                def.type      = new_type;
                def.item      = new_item;

                var type_translation_key = "COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + String(def.type)
                    .toUpperCase()
                    .substr(5);

                trow.querySelector("td.ak-type span").textContent = Joomla.Text._(type_translation_key);
                trow.querySelector("td.ak-item code").textContent   = akeebabackup.System.escapeHTML(def.item);
                akeebabackup.System.triggerEvent(tdFirst.querySelector("span.cancel"), "click");

                if (haveToDelete)
                {
                    new_data.verb = "remove";
                    akeebabackup.Fsfilters.toggle(new_data, this, function (response, caller)
                    {
                    }, false);
                }
                else if ((def.item != new_item) || (def.type != new_type) || addedNewItem)
                {
                    akeebabackup.Regexfsfilters.addNewRow(append_to_here);
                }
            }, false);
        });
        tdFirst.appendChild(elSave);

        var elCancel = document.createElement("span");

        elCancel.className = "table-icon-container btn btn-warning btn-sm cancel ak-toggle-button";

        elCancel.insertAdjacentHTML("beforeend", "<span class=\"fa fa-times-circle\"></span>");

        elCancel.addEventListener("click", function ()
        {
            var tdFirst = trow.children[0];

            // Cancel changes; remove editing GUI elements, show the original elements
            var elSave = tdFirst.querySelector("span.save");
            elSave.parentNode.removeChild(elSave);

            var elCancel = tdFirst.querySelector("span.cancel");
            elCancel.parentNode.removeChild(elCancel);

            tdFirst.querySelector("span.edit").style.display = "inline-block";
            if (def.item != "")
            {
                tdFirst.querySelector("span.delete").style.display = "inline-block";
            }

            mySelect.parentNode.removeChild(mySelect);
            trow.querySelector("td.ak-type span").style.display = "inline-block";
            myEditBox.parentNode.removeChild(myEditBox);
            trow.querySelector("td.ak-item code").style.display = "inline";
        });

        tdFirst.appendChild(elCancel);
    });

    elDelete.className = "table-icon-container btn btn-danger btn-sm delete ak-toggle-button";
    elDelete.insertAdjacentHTML("beforeend", "<span class=\"fa fa-trash\"></span>");

    elDelete.addEventListener("click", function ()
    {
        var elRoot = document.getElementById("active_root");

        var new_data = {
            verb: "remove",
            type: def.type,
            node: def.item,
            root: elRoot.options[elRoot.selectedIndex].value
        };

        akeebabackup.Fsfilters.toggle(new_data, this, function (response, caller)
        {
            trow.parentNode.removeChild(trow);
            if (def.item == "")
            {
                akeebabackup.Regexfsfilters.addNewRow(append_to_here);
            }
        }, false);
    });

    // Hide the delete button on new rows
    if (def.item == "")
    {
        td_buttons.querySelector("span.delete").style.display = "none";
    }

    // Filter type and filter item rows
    var type_translation_key = "COM_AKEEBABACKUP_FILEFILTERS_TYPE_" + String(def.type).toUpperCase().substr(5);
    var type_localized       = Joomla.Text._(type_translation_key);
    if (def.type == "")
    {
        type_localized = "";
    }

    var elType       = document.createElement("td");
    elType.className = "ak-type";
    elType.innerHTML = "<span>" + type_localized + "</span>";
    trow.appendChild(elType);

    var elItem       = document.createElement("td");
    elItem.className = "ak-item";
    elItem.innerHTML = "<code>" + ((def.item == null) ? "" : akeebabackup.System.escapeHTML(def.item)) + "</code>";
    trow.appendChild(elItem);
};

/**
 * Add a new row to the GUI
 *
 * @param   append_to_here  Element where to append the row
 */
akeebabackup.Regexfsfilters.addNewRow = function (append_to_here)
{
    var newdef = {
        type: "",
        item: ""
    };
    akeebabackup.Regexfsfilters.addRow(newdef, append_to_here);
};

akeebabackup.System.documentReady(function ()
{
    document.getElementById("active_root")
            .addEventListener("change", akeebabackup.Regexfsfilters.activeRootChanged);

    akeebabackup.Regexfsfilters.render(Joomla.getOptions("akeebabackup.Regexfilefilters.guiData", {}));
});