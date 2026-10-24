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

if (typeof akeebabackup.Configuration === "undefined")
{
    akeebabackup.Configuration = {
        GUI:            {
            /**
             * Renders the label of a configuration option, appending it to the container element
             *
             * @param   {string}   current_id  The input name, e.g. var[something.or.another]
             * @param   {Object}   defdata     The option definition data
             * @param   {Element}  row_div    The element which contains the option itself (the DIV of the current row)
             */
            renderOptionLabel:
                function (current_id, defdata, row_div)
                {
                    const noLabel = (defdata['nolabel'] ?? 0) != 0;

                    // No interface is rendered for 'hidden' and 'none' option types
                    if (noLabel || (defdata["type"] === "hidden") || (defdata["type"] === "none"))
                    {
                        return;
                    }

                    // Create label
                    var label       = document.createElement("label");
                    label.className = "col-sm-3 col-form-label";
                    label.setAttribute("for", current_id);
                    label.innerHTML = defdata["title"];

                    if (defdata["description"])
                    {
                        label.setAttribute("rel", "popover");
                        label.setAttribute("title", defdata["title"]);
                        label.setAttribute("data-bs-content", defdata["description"]);
                    }

                    if (defdata["bold"])
                    {
                        label.className += ' fw-bold';
                    }

                    row_div.appendChild(label);
                },

            /**
             * Renders an option of type "none". A do-not-display field. It doesn't render any input element at all.
             *
             * @param   {string}  current_id       The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata          The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeNone:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    // Nothing to render
                },

            /**
             * Renders an option of type "hidden". A hidden field.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeHidden:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var hiddenfield = document.createElement("input");
                    hiddenfield.id  = current_id;
                    hiddenfield.setAttribute("type", "hidden");
                    hiddenfield.setAttribute("name", current_id);
                    hiddenfield.value = defdata["default"];

                    container.appendChild(hiddenfield);
                },

            /**
             * Renders an option of type "separator". A GUI row separator.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeSeparator:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    const element   = defdata["element"] ?? "div";
                    const content   = defdata["content"] ?? null;
                    const separator = document.createElement(element);

                    if (content)
                    {
                        separator.innerHTML = content;
                    }

                    container.appendChild(separator);
                },

            /**
             * Renders an option of type "installer". An installer selection.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeInstaller:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    // Create the select element
                    var editor       = document.createElement("select");
                    editor.className = "form-select akeeba-configuration-select-installer";
                    editor.id        = current_id;
                    editor.setAttribute("name", current_id);

                    for (var key in akeebabackup.Configuration.installers)
                    {
                        if (!akeebabackup.Configuration.installers.hasOwnProperty(key))
                        {
                            continue;
                        }

                        var element = akeebabackup.Configuration.installers[key];

                        var option       = document.createElement("option");
                        option.value     = key;
                        option.innerHTML = element.name;

                        if (defdata["default"] === key)
                        {
                            option.setAttribute("selected", 1);
                        }

                        editor.appendChild(option);
                    }

                    controlWrapper.appendChild(editor);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "engine". An engine selection.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeEngine:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var engine_type = defdata["subtype"];

                    if (akeebabackup.Configuration.engines[engine_type] == null)
                    {
                        return;
                    }

                    var config_key = current_id.substr(4, current_id.length - 5);

                    // Container for engine parameters, initially hidden
                    var engine_config_container       = document.createElement("div");
                    engine_config_container.id        = config_key + "_config";
                    engine_config_container.className = "akeeba-engine-options";

                    // Create the select element
                    var editor = document.createElement("select");
                    editor.className = 'form-select';
                    editor.id  = current_id;
                    editor.setAttribute("name", current_id);

                    var engineOptions = akeebabackup.Configuration.engines[engine_type];

                    for (var key in engineOptions)
                    {
                        if (!engineOptions.hasOwnProperty(key))
                        {
                            continue;
                        }

                        var element = engineOptions[key];

                        var option       = document.createElement("option");
                        option.value     = key;
                        option.innerHTML = element.information.title;

                        if (defdata["default"] == key)
                        {
                            option.setAttribute("selected", "selected");
                        }

                        editor.appendChild(option);
                    }

                    akeebabackup.System.addEventListener(editor, "change", function (e)
                    {
                        // When the selection changes, we have to repopulate the config container
                        // First, save any changed values
                        var old_values = {};

                        var allElements = [
                            document.getElementById(config_key + "_config").querySelectorAll("input"),
                            document.getElementById(config_key + "_config").querySelectorAll("select")
                        ];

                        var allInputs = null;
                        var input     = null;
                        var id        = null;

                        for (var i = 0; i < allElements.length; i++)
                        {
                            allInputs = allElements[i];

                            if (!allInputs.length)
                            {
                                continue;
                            }

                            for (j = 0; j < allInputs.length; j++)
                            {
                                input = allInputs[j];
                                id    = input.id;

                                old_values[id] = input.value;

                                if ((input.getAttribute("type") === "checkbox") || (input.getAttribute("type") === "radio"))
                                {
                                    old_values[id] = input.checked;
                                }
                                else if (input.getAttribute("type") === "select")
                                {
                                    old_values[id] = input.options[input.selectedIndex].value;
                                }
                            }

                        }

                        // Create the new interface
                        var new_engine        = editor.value;
                        var enginedef         = akeebabackup.Configuration.engines[engine_type][new_engine];
                        var enginetitle       = enginedef.information.title;
                        var new_data          = {};
                        new_data[enginetitle] = enginedef.parameters;

                        akeebabackup.Configuration.parseGuiData(new_data, engine_config_container);

                        var elCardBody = engine_config_container.querySelector("div.card-body");
                        if (elCardBody instanceof Element)
                        {
                            elCardBody.insertAdjacentHTML(
                                "afterbegin",
                                "<p class=\"alert alert-info\">" + enginedef.information.description + "</p>"
                            );
                        }

                        // Reapply changed values
                        allElements = [
                            document.getElementById(config_key + "_config").querySelectorAll("input"),
                            document.getElementById(config_key + "_config").querySelectorAll("select")
                        ];

                        for (i = 0; i < allElements.length; i++)
                        {
                            allInputs = allElements[i];

                            if (!allInputs.length)
                            {
                                continue;
                            }

                            for (var j = 0; j < allInputs.length; j++)
                            {
                                input = allInputs[j];
                                id    = input.id;

                                var old = old_values[id];

                                if ((old == null))
                                {
                                    continue;
                                }

                                if ((input.getAttribute("type") === "checkbox") || (input.getAttribute("type") === "radio"))
                                {
                                    if (old)
                                    {
                                        input.setAttribute("checked", "checked");
                                    }
                                    else
                                    {
                                        input.removeAttribute("checked");
                                    }
                                }
                                else
                                {
                                    input.value = old;
                                }

                                // Trigger the change event for drop-downs
                                if (i === 1)
                                {
                                    akeebabackup.System.triggerEvent(input, "change");
                                }
                            }
                        }

                        // Initialise the new popovers
                        akeebabackup.Configuration.initialisePopovers();

                        // Finally, run the activation_callback
                        if (typeof enginedef.information.activation_callback !== "undefined")
                        {
                            window[enginedef.information.activation_callback](enginedef.parameters);
                        }
                    });

                    // Add a configuration show/hide button
                    var button       = document.createElement("button");
                    button.className = "btn btn-secondary btn-sm";

                    var icon       = document.createElement("span");
                    icon.className = "fa fa-wrench";
                    button.appendChild(icon);

                    var btnText       = document.createElement("span");
                    btnText.innerHTML = Joomla.Text._("COM_AKEEBABACKUP_CONFIG_UI_CONFIG");
                    button.appendChild(btnText);

                    akeebabackup.System.addEventListener(button, "click", function (e)
                    {
                        e.preventDefault();

                        var bsCollapse = new bootstrap.Collapse(engine_config_container);
                        bsCollapse.toggle();
                    });

                    var inputGroupWrapper = document.createElement('div');
                    inputGroupWrapper.className = 'input-group';
                    inputGroupWrapper.appendChild(editor);
                    // inputGroupWrapper.appendChild(button);

                    controlWrapper.appendChild(inputGroupWrapper);
                    controlWrapper.appendChild(engine_config_container);

                    row_div.appendChild(controlWrapper);

                    // Populate config container with the default engine data
                    akeebabackup.System.triggerEvent(editor, 'change');
                },

            /**
             * Renders an option of type "browsedir". A text box with an option to launch a browser.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeBrowsedir:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var editor = document.createElement("input");
                    editor.className = 'form-control';
                    editor.setAttribute("type", "text");
                    editor.setAttribute("name", current_id);
                    editor.setAttribute("size", "30");
                    editor.id    = current_id;
                    editor.value = defdata["default"];

                    var button       = document.createElement("button");
                    button.className = "btn btn-secondary";
                    button.setAttribute("title", Joomla.Text._("COM_AKEEBABACKUP_CONFIG_UI_BROWSE"));

                    var icon       = document.createElement("span");
                    icon.className = "fa fa-folder-open";
                    button.appendChild(icon);

                    akeebabackup.System.addEventListener(button, "click", function (e)
                    {
                        e.preventDefault();

                        if (akeebabackup.Configuration.onBrowser != null)
                        {
                            akeebabackup.Configuration.onBrowser(editor.value, editor);
                        }

                        return false;
                    });

                    var containerDiv       = document.createElement("div");
                    containerDiv.className = "input-group";

                    containerDiv.appendChild(editor);
                    containerDiv.appendChild(button);

                    controlWrapper.appendChild(containerDiv);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "enum". A drop-down list.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeEnum:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var editor       = document.createElement("select");
                    editor.className = "form-select akeeba-configuration-select-enum";
                    editor.id        = current_id;
                    editor.setAttribute("name", current_id);

                    // Create and append options
                    var enumvalues = defdata["enumvalues"].split("|");
                    var enumkeys   = defdata["enumkeys"].split("|");

                    for (var counter = 0; counter < enumvalues.length; counter++)
                    {
                        var value = enumvalues[counter];

                        var item_description = enumkeys[counter];
                        var option           = document.createElement("option");
                        option.value         = value;
                        option.innerHTML     = item_description;

                        if (value === defdata["default"])
                        {
                            option.setAttribute("selected", "selected");
                        }

                        editor.appendChild(option);
                    }

                    if (typeof defdata["onchange"] !== "undefined")
                    {
                        akeebabackup.System.addEventListener(editor, "change", function ()
                        {
                            var callback_onchange = defdata["onchange"];
                            callback_onchange(editor);
                        });
                    }

                    controlWrapper.appendChild(editor);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "string". A simple single-line, unvalidated text box.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeString:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var editor       = document.createElement("input");
                    editor.className = "form-control akeeba-configuration-string";
                    editor.setAttribute("type", "text");
                    editor.id = current_id;
                    editor.setAttribute("name", current_id);
                    editor.value = defdata["default"];

                    controlWrapper.appendChild(editor);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "password". A simple single-line, unvalidated password box.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypePassword:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    akeebabackup.Configuration.passwordFields[current_id] = defdata["default"];

                    var editor       = document.createElement("input");
                    editor.className = "form-control akeeba-configuration-password";
                    editor.setAttribute("type", "password");
                    editor.id = current_id;
                    editor.setAttribute("name", current_id);
                    editor.setAttribute("size", 40);
                    editor.value = defdata["default"];
                    editor.setAttribute("autocomplete", "off");

                    controlWrapper.appendChild(editor);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "integer". Hidden form element with the real value.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeInteger:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var config_key = current_id.substr(4, current_id.length - 5);

                    // Hidden input field. Holds the actual value saved to the configuration.
                    var elHiddenInput = document.createElement("input");
                    elHiddenInput.id  = config_key;
                    elHiddenInput.setAttribute("name", current_id);
                    elHiddenInput.setAttribute("type", "hidden");
                    elHiddenInput.value = defdata["default"];

                    // Custom value input box
                    var minValue = defdata["min"] / defdata["scale"];
                    var maxValue = defdata["max"] / defdata["scale"];
                    var stepValue = 1 / defdata["scale"];

                    if (defdata["scale"] > 1)
                    {
                        minValue = minValue.toFixed(2);
                        maxValue = maxValue.toFixed(2);
                        stepValue = stepValue.toFixed(2);
                    }
                    else
                    {
                        minValue = Math.trunc(minValue);
                        maxValue = Math.trunc(maxValue);
                        stepValue = Math.trunc(stepValue);
                    }

                    var elCustomValue = document.createElement("input");
                    elCustomValue.className = 'form-control';
                    elCustomValue.setAttribute("type", "number");
                    elCustomValue.id            = config_key + "_custom";
                    elCustomValue.style.display = "none";
                    elCustomValue.setAttribute("min", minValue);
                    elCustomValue.setAttribute("max", maxValue);
                    elCustomValue.setAttribute("step", stepValue);

                    var updateHiddenFromCustom = function ()
                    {
                        var value = parseFloat(elCustomValue.value);
                        value     = value * defdata["scale"];

                        if (value < defdata["min"])
                        {
                            value = defdata["min"];
                        }
                        else if (value > defdata["max"])
                        {
                            value = defdata["max"];
                        }

                        elHiddenInput.value = value;

                        var newValue = value / defdata["scale"];

                        if (defdata["scale"] > 1)
                        {
                            elCustomValue.value = newValue.toFixed(2);
                        }
                        else
                        {
                            elCustomValue.value = Math.trunc(newValue);
                        }
                    };

                    akeebabackup.System.addEventListener(elCustomValue, "change", updateHiddenFromCustom);
                    akeebabackup.System.addEventListener(elCustomValue, "blur", updateHiddenFromCustom);

                    // Select element with preset options
                    var elDropdown = document.createElement("select");
                    elDropdown.id  = config_key + "_dropdown";
                    elDropdown.setAttribute("name", config_key + "_dropdown");
                    elDropdown.className = "form-select";

                    // Create and append the preset options to the select element
                    var enumvalues     = defdata["shortcuts"].split("|");
                    var quantizer      = defdata["scale"];
                    var isPresetOption = false;

                    for (var counter = 0; counter < enumvalues.length; counter++)
                    {
                        var value = enumvalues[counter];

                        var item_description       = value / quantizer;
                        var elDropdownOption       = document.createElement("option");
                        elDropdownOption.value     = value;
                        elDropdownOption.innerHTML = (quantizer > 1) ? item_description.toFixed(2) : Math.trunc(item_description);

                        if (value == defdata["default"])
                        {
                            elDropdownOption.setAttribute("selected", "selected");
                            isPresetOption = true;
                        }

                        elDropdown.appendChild(elDropdownOption);
                    }

                    // Create one last option called "Custom"
                    var option       = document.createElement("option");
                    option.value     = -1;
                    option.innerHTML = Joomla.Text._('COM_AKEEBABACKUP_CONFIG_UI_CUSTOM');

                    if (!isPresetOption)
                    {
                        option.setAttribute("selected", "selected");
                        elCustomValue.value         = (defdata["default"] / defdata["scale"]).toFixed(2);
                        elCustomValue.style.display = "block";
                    }

                    elDropdown.appendChild(option);

                    // Add actions to the dropdown
                    akeebabackup.System.addEventListener(elDropdown, "change", function ()
                    {
                        var value = elDropdown.value;

                        if (value === '-1')
                        {
                            elCustomValue.value         = (defdata["default"] / defdata["scale"]).toFixed(2);
                            elCustomValue.style.display = "block";
                            akeebabackup.System.triggerEvent(elCustomValue, "focus");

                            return;
                        }

                        elHiddenInput.value         = value;
                        elCustomValue.style.display = "none";
                    });

                    var inputGroupWrapper = document.createElement('div');
                    inputGroupWrapper.className = 'input-group';

                    // Label
                    var uom = defdata["uom"];

                    inputGroupWrapper.appendChild(elDropdown);
                    inputGroupWrapper.appendChild(elCustomValue);

                    if ((typeof (uom) === "string") && (uom.length !== 0))
                    {
                        var label         = document.createElement("span");
                        label.className = 'input-group-text';
                        label.textContent = uom;

                        inputGroupWrapper.appendChild(label);
                    }

                    controlWrapper.appendChild(inputGroupWrapper);
                    controlWrapper.appendChild(elHiddenInput);

                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "bool". A toggle button.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeBool:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var newId = current_id.match(/[a-z0-9]/ig).join('');

                    var wrap_div       = document.createElement("div");
                    wrap_div.className = "btn-group";
                    wrap_div.setAttribute('role', 'group');

                    var elYesInput = document.createElement('input');
                    elYesInput.type = 'radio';
                    elYesInput.className = 'btn-check';
                    elYesInput.name = current_id;
                    elYesInput.setAttribute('autocomplete', 'off');
                    elYesInput.id = newId + '_1';
                    elYesInput.value = '1';

                    var elYesLabel = document.createElement('label');
                    elYesLabel.className = 'btn btn-outline-success';
                    elYesLabel.setAttribute('for', newId + '_1');
                    elYesLabel.innerText = Joomla.Text._('JYES');

                    var elNoInput = document.createElement('input');
                    elNoInput.type = 'radio';
                    elNoInput.className = 'btn-check';
                    elNoInput.name = current_id;
                    elNoInput.setAttribute('autocomplete', 'off');
                    elNoInput.id = newId + '_0';
                    elNoInput.value = 0;

                    var elNoLabel = document.createElement('label');
                    elNoLabel.className = 'btn btn-outline-danger';
                    elNoLabel.setAttribute('for', newId + '_0');
                    elNoLabel.innerText = Joomla.Text._('JNO');

                    if (defdata["default"] != 0)
                    {
                        elYesInput.setAttribute("checked", "checked");
                    }
                    else
                    {
                        elNoInput.setAttribute("checked", "checked");
                    }

                    wrap_div.appendChild(elYesInput);
                    wrap_div.appendChild(elYesLabel);
                    wrap_div.appendChild(elNoInput);
                    wrap_div.appendChild(elNoLabel);
                    controlWrapper.appendChild(wrap_div);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of type "button". Button with a custom hook function.
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeButton:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    // Create the button
                    var label        = row_div.querySelector("label");
                    var hook         = defdata["hook"];
                    var labeltext    = label.innerHTML;
                    var editor       = document.createElement("button");
                    editor.id        = current_id;
                    editor.innerHTML = labeltext;
                    editor.className = "btn btn-secondary";
                    label.innerHTML  = "&nbsp;";

                    akeebabackup.System.addEventListener(editor, "click", function (e)
                    {
                        e.preventDefault();

                        try
                        {
                            window[hook]();
                        }
                        catch (err)
                        {
                        }
                    });

                    controlWrapper.appendChild(editor);
                    row_div.appendChild(controlWrapper);
                },

            /**
             * Renders an option of an unknown type (an extension is used).
             *
             * @param   {string}  current_id      The input name, e.g. var[something.or.another]
             * @param   {Object}  defdata         The option definition data+
             * @param   {Element} controlWrapper  The element which contains the option's input object
             * @param   {Element} row_div         The element which contains the option itself (the DIV of the current
             *     row)
             * @param   {Element} container       The element which contains the row_div (option group container)
             */
            renderOptionTypeUnknown:
                function (current_id, defdata, controlWrapper, row_div, container)
                {
                    var config_key = current_id.substr(4, current_id.length - 5);

                    var method = "akeeba_render_" + defdata["type"];
                    var fn     = window[method];

                    if (typeof fn == "function")
                    {
                        fn(config_key, defdata, label, row_div);
                    }
                    else
                    {
                        try
                        {
                            window[method](config_key, defdata, label, row_div);
                        }
                        catch (e)
                        {

                        }
                    }
                }
        },
        engines:        {},
        installers:     {},
        URLs:           {},
        FtpTest:        {
            testConnection:
                function (buttonKey, configKey, isCurl)
                {
                    var button = document.getElementById("var[" + buttonKey + "]");

                    akeebabackup.Configuration.FtpTest.buttonKey = "var[" + buttonKey + "]";

                    if (button === null)
                    {
                        button = document.getElementById(buttonKey);

                        akeebabackup.Configuration.FtpTest.buttonKey = buttonKey;
                    }

                    if (button === null)
                    {
                        console.warn("Button " + akeebabackup.Configuration.FtpTest.buttonKey + " not found");

                        return;
                    }

                    button.setAttribute("disabled", "disabled");

                    var data = {};

                    try
                    {
                        var ssl_key = "var[" + configKey + ".ftps]";
                        var passive_key = "var[" + configKey + ".passive_mode]"

                        data = {
                            isCurl:                  (isCurl ? 1 : 0),
                            host:                    document.getElementById("var[" + configKey + ".host]").value,
                            port:                    document.getElementById("var[" + configKey + ".port]").value,
                            user:                    document.getElementById("var[" + configKey + ".user]").value,
                            pass:                    document.getElementById("var[" + configKey + ".pass]").value,
                            initdir:                 document.getElementById("var[" + configKey + ".initial_directory]").value,
                            usessl:                  document.querySelector('input[name="' + ssl_key +'"]:checked').value,
                            passive:                 document.querySelector('input[name="' + passive_key +'"]:checked').value,
                            passive_mode_workaround: 0
                        };
                    }
                    catch (e)
                    {
                        data = {
                            isCurl:                  (isCurl ? 1 : 0),
                            host:                    document.getElementById(configKey + "_host").value,
                            port:                    document.getElementById(configKey + "_port").value,
                            user:                    document.getElementById(configKey + "_user").value,
                            pass:                    document.getElementById(configKey + "_pass").value,
                            initdir:                 document.getElementById(configKey + "_initial_directory").value,
                            usessl:                  document.getElementById(configKey + "_ftps").checked,
                            passive:                 document.getElementById(configKey + "_passive_mode").checked,
                            passive_mode_workaround: 0
                        };
                    }

                    // The passive_mode_workaround input is only defined for cURL
                    if (isCurl)
                    {
                        try
                        {
                            data.passive_mode_workaround =
                                document.querySelector('input[name="var[' + configKey + '.passive_mode_workaround]"]:checked').value;
                        }
                        catch (e)
                        {
                            data.passive_mode_workaround =
                                document.getElementById(configKey + "_passive_mode_workaround").checked;
                        }
                    }

                    // Construct the query
                    akeebabackup.System.params.AjaxURL = akeebabackup.Configuration.URLs.testFtp;

                    akeebabackup.System.doAjax(
                        data,
                        function (res)
                        {
                            var button = document.getElementById(akeebabackup.Configuration.FtpTest.buttonKey);
                            button.removeAttribute("disabled");

                            var elTestFTPBodyOK   = document.getElementById("testFtpDialogBodyOk");
                            var elTestFTPBodyFail = document.getElementById("testFtpDialogBodyFail");
                            var elTestFTPLabel    = document.getElementById("testFtpDialogLabel");

                            elTestFTPBodyOK.style.display   = "none";
                            elTestFTPBodyFail.style.display = "none";

                            if (res?.status === true)
                            {
                                elTestFTPLabel.textContent      =
                                    Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_OK");
                                elTestFTPBodyOK.textContent     =
                                    Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_OK");
                                elTestFTPBodyOK.style.display   = "block";
                                elTestFTPBodyFail.style.display = "none";
                            }
                            else
                            {
                                elTestFTPLabel.textContent      =
                                    Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_FAIL");
                                elTestFTPBodyFail.textContent   = res?.status ?? '???';
                                elTestFTPBodyOK.style.display   = "none";
                                elTestFTPBodyFail.style.display = "block";
                            }

                            // Use Bootstrap to open the modal
                            var myModal = new bootstrap.Modal(document.getElementById('testFtpDialog'), {
                                keyboard: true,
                                backdrop: true
                            });
                            myModal.show();

                        }, null, false, 15000
                    )
                }
        },
        SftpTest:       {
            testConnection:
                function (buttonKey, configKey, isCurl)
                {
                    var button                              = document.getElementById("var[" + buttonKey + "]");
                    akeebabackup.Configuration.SftpTest.buttonKey = "var[" + buttonKey + "]";

                    button.setAttribute("disabled", "disabled");

                    var data = {
                        isCurl:  (isCurl ? 1 : 0),
                        host:    document.getElementById("var[" + configKey + ".host]").value,
                        port:    document.getElementById("var[" + configKey + ".port]").value,
                        user:    document.getElementById("var[" + configKey + ".user]").value,
                        pass:    document.getElementById("var[" + configKey + ".pass]").value,
                        initdir: document.getElementById("var[" + configKey + ".initial_directory]").value,
                        privkey: document.getElementById("var[" + configKey + ".privkey]").value,
                        pubkey:  document.getElementById("var[" + configKey + ".pubkey]").value
                    };

                    // Construct the query
                    akeebabackup.System.params.AjaxURL = akeebabackup.Configuration.URLs.testSftp;

                    akeebabackup.System.doAjax(
                        data,
                        function (res)
                        {
                            var button = document.getElementById(akeebabackup.Configuration.SftpTest.buttonKey);
                            button.removeAttribute("disabled");

                            var elTestFTPBodyOK   = document.getElementById("testFtpDialogBodyOk");
                            var elTestFTPBodyFail = document.getElementById("testFtpDialogBodyFail");
                            var elTestFTPLabel    = document.getElementById("testFtpDialogLabel");

                            elTestFTPBodyOK.style.display   = "none";
                            elTestFTPBodyFail.style.display = "none";

                            if (res?.status === true)
                            {
                                elTestFTPLabel.textContent      =
                                    Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTSFTP_TEST_OK");
                                elTestFTPBodyOK.textContent     =
                                    Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTSFTP_TEST_OK");
                                elTestFTPBodyOK.style.display   = "block";
                                elTestFTPBodyFail.style.display = "none";
                            }
                            else
                            {
                                elTestFTPLabel.textContent      =
                                    Joomla.Text._("COM_AKEEBABACKUP_CONFIG_DIRECTSFTP_TEST_FAIL");
                                elTestFTPBodyFail.textContent   = res?.status ?? '???';
                                elTestFTPBodyOK.style.display   = "none";
                                elTestFTPBodyFail.style.display = "block";
                            }

                            // Use Bootstrap to open the modal
                            var myModal = new bootstrap.Modal(document.getElementById('testFtpDialog'), {
                                keyboard: true,
                                backdrop: true
                            });
                            myModal.show();
                        }, null, false, 15000
                    )
                }
        },
        FtpModal:       null,
        passwordFields: {},
        fsBrowser:      {
            params:      {
                dialogId:     "folderBrowserDialog",
                dialogBodyId: "folderBrowserDialogBody"
            },
            modalObject: null
        },

        /**
         * Parses the JSON decoded data object defining engine and GUI parameters for the
         * configuration page
         *
         * @param  data  The nested objects of engine and GUI definitions
         */
        parseConfigData:
            function (data)
            {
                akeebabackup.Configuration.engines    = data.engines;
                akeebabackup.Configuration.installers = data.installers;
                akeebabackup.Configuration.parseGuiData(data.gui);
            },

        /**
         * Restores the contents of the password fields after brain-dead browsers with broken password managers try to
         * auto-fill the wrong password to the wrong field without warning you or asking you.
         */
        restoreDefaultPasswords:
            function ()
            {
                for (var curid in akeebabackup.Configuration.passwordFields)
                {
                    if (!akeebabackup.Configuration.passwordFields.hasOwnProperty(curid))
                    {
                        continue;
                    }

                    var defvalue = akeebabackup.Configuration.passwordFields[curid];

                    var myElement = document.getElementById(curid);

                    if (!myElement)
                    {
                        continue;
                    }

                    // Do not remove this line. It's required when defvalue is empty. Why? BECAUSE BROWSERS ARE BRAIN
                    // DEAD!
                    myElement.value = "WORKAROUND FOR NAUGHTY BROWSERS";
                    // This line finally sets the fields back to its default value.
                    myElement.value = defvalue;
                }
            },

        /**
         * Opens a filesystem folder browser
         *
         * @param  folder   The folder to start browsing from
         * @param  element  The element whose value we'll modify when this browser returns
         */
        onBrowser:
            function (folder, element)
            {
                // Close dialog callback (user confirmed the new folder)
                akeebabackup.Configuration.onBrowserCallback = function (myFolder)
                {
                    element.value = myFolder;

                    if ((typeof akeebabackup.Configuration.fsBrowser.modalObject == "object") && akeebabackup.Configuration.fsBrowser.modalObject.close)
                    {
                        akeebabackup.Configuration.fsBrowser.modalObject.close()
                    }
                    else if ((typeof akeebabackup.Configuration.fsBrowser.modalObject == "object") && akeebabackup.Configuration.fsBrowser.modalObject.hide)
                    {
                        akeebabackup.Configuration.fsBrowser.modalObject.hide()
                    }
                };

                // URL to load the browser
                var browserSrc = akeebabackup.Configuration.URLs["browser"] + encodeURIComponent(folder);

                var dialogBody = document.getElementById(akeebabackup.Configuration.fsBrowser.params.dialogBodyId);

                dialogBody.innerHTML = "";

                var iFrame = document.createElement("iframe");
                iFrame.setAttribute("src", browserSrc);
                iFrame.setAttribute("width", "100%");
                iFrame.setAttribute("height", 400);
                iFrame.setAttribute("frameborder", 0);
                iFrame.setAttribute("allowtransparency", "true");

                dialogBody.appendChild(iFrame);

                // Use Bootstrap to open the modal
                akeebabackup.Configuration.fsBrowser.modalObject = new bootstrap.Modal(
                    document.getElementById(akeebabackup.Configuration.fsBrowser.params.dialogId), {
                        keyboard: false,
                        backdrop: 'static'
                    });
                akeebabackup.Configuration.fsBrowser.modalObject.show();
            },

        /**
         * Parses the main configuration GUI definition, generating the on-page widgets
         *
         * @param  data      The nested objects of the GUI definition ('gui' key of JSON data)
         * @param  rootnode  The jroot DOM element in which to create the widgets
         */
        parseGuiData:
            function (data, rootnode)
            {
                if (rootnode == null)
                {
                    // The default root node is the form itself
                    rootnode = document.getElementById("akeebagui");
                }

                // Begin by slashing contents of the akeebagui DIV
                rootnode.innerHTML = "";

                // This is the workhorse, looping through groupdefs and creating HTML elements
                var group_id = 0;

                for (var headertext in data)
                {
                    if (!data.hasOwnProperty(headertext))
                    {
                        continue;
                    }

                    var groupdef = data[headertext];

                    // Loop for each group definition
                    group_id++;

                    if (!groupdef)
                    {
                        continue;
                    }

                    // Each group is a Bootstrap card. The outer container which is appended to the root node.
                    var cardOuterWrapper = document.createElement('div');
                    cardOuterWrapper.className = 'card mt-3 rounded-top';
                    rootnode.appendChild(cardOuterWrapper);

                    // The card has a header which is appended to the outer wrapper
                    var header = document.createElement('h3');
                    header.className = 'card-header';
                    header.innerHTML = headertext;
                    header.id = "auigrp_" + rootnode.id + "_" + group_id;
                    cardOuterWrapper.appendChild(header);

                    // All the options are rendered inside the card body
                    var container       = document.createElement("div");
                    container.className = "card-body";
                    cardOuterWrapper.appendChild(container);

                    // Loop each element, rendering a row that gets appended to the container.
                    for (var config_key in groupdef)
                    {
                        if (!groupdef.hasOwnProperty(config_key))
                        {
                            continue;
                        }

                        var defdata = groupdef[config_key];

                        // Parameter ID
                        var current_id = "var[" + config_key + "]";

                        // Option row DIV
                        var showOn        = defdata['showon'] ?? null;
                        var row_div       = document.createElement("div");
                        row_div.className = "row mb-3";
                        row_div.id        = "akconfigrow." + config_key;

                        if (showOn)
                        {
                            row_div.dataset.showon = JSON.stringify(showOn);
                        }
                        /**
                         * We must append the option row to the container only if the option type is NOT 'hidden' or
                         * 'none'. These two option types are non-GUI elements. We only render a hidden field for them.
                         * The hidden field is rendered without a row container so that we don't create an empty row in
                         * the interface.
                         */
                        if ((defdata["type"] != "hidden") && (defdata["type"] != "none"))
                        {
                            container.appendChild(row_div);
                        }

                        // Render the label, if applicable
                        akeebabackup.Configuration.GUI.renderOptionLabel(current_id, defdata, row_div);

                        // Create GUI representation based on type
                        var controlWrapper       = document.createElement("div");
                        controlWrapper.className = "col-sm-9";

                        var ucfirstType  = defdata["type"][0].toUpperCase() + defdata["type"].slice(1);
                        var renderMethod = "renderOptionType" + ucfirstType;

                        if (typeof akeebabackup.Configuration.GUI[renderMethod] === "function")
                        {
                            akeebabackup.Configuration.GUI[renderMethod](
                                current_id, defdata, controlWrapper, row_div, container);
                        }
                        else
                        {
                            akeebabackup.Configuration.GUI.renderOptionTypeUnknown(current_id, defdata, controlWrapper,
                                row_div,
                                container
                            );
                        }
                    }
                }

                // Re-initialise the ShowOn JavaScript
                Joomla.Showon.initialise(rootnode);
            },

        onChangeScriptType:
            function (selectElement)
            {
                // Currently selected value
                var value             = selectElement.options[selectElement.selectedIndex].value;
                var possibleInstaller = (value === "joomla") ? "angie" : ("angie-" + value);

                // All possible installers
                var installerSelect   = document.getElementById("var[akeeba.advanced.embedded_installer]");
                var installerElements = installerSelect.children;

                for (var i = 0; i < installerElements.length; i++)
                {
                    var element = installerElements[i];

                    if (element.value === possibleInstaller)
                    {
                        installerSelect.value = possibleInstaller;

                        return;
                    }
                }
            },

        initialisePopovers:
            function()
            {
                var popovers = Joomla.getOptions('bootstrap.popover');

                if (typeof popovers !== 'object' || popovers === null)
                {
                    return;
                }

                Object.keys(popovers).forEach(function (popover)
                {
                    var opt     = popovers[popover];
                    var options = {
                        animation:         opt.animation ? opt.animation : true,
                        container:         opt.container ? opt.container : false,
                        //content:           opt.content ? opt.content : "",
                        delay:             opt.delay ? opt.delay : 0,
                        html:              opt.html ? opt.html : false,
                        placement:         opt.placement ? opt.placement : "top",
                        selector:          opt.selector ? opt.selector : false,
                        //title:             opt.title ? opt.title : "",
                        trigger:           opt.trigger ? opt.trigger : "click",
                        offset:            opt.offset ? opt.offset : 0,
                        fallbackPlacement: opt.fallbackPlacement ? opt.fallbackPlacement : "flip",
                        boundary:          opt.boundary ? opt.boundary : "scrollParent",
                        customClass:       opt.customClass ? opt.customClass : "",
                        sanitize:          opt.sanitize ? opt.sanitize : true,
                        sanitizeFn:        opt.sanitizeFn ? opt.sanitizeFn : null,
                        popperConfig:      opt.popperConfig ? opt.popperConfig : null,
                    };

                    if (opt.template)
                    {
                        options.template = opt.template;
                    }
                    if (opt.allowList)
                    {
                        options.allowList = opt.allowList;
                    }

                    var elements = Array.from(document.querySelectorAll(popover));
                    if (elements.length)
                    {
                        elements.map(function (el)
                        {
                            new window.bootstrap.Popover(el, options)
                        });
                    }
                });
            },

        onFormSubmit: (event) => {
            const adminForm = document.getElementById("adminForm");

            // Old browsers: submit the form with 100+ parameters.
            if (
                !adminForm || typeof window.FormData === "undefined"
                || typeof Object.fromEntries === "undefined" || typeof JSON.stringify === "undefined"
            )
            {
                return true;
            }

            // Newer browsers: submit as a single JSON document
            event.preventDefault();

            akeebabackup.Configuration.submitJsonForm(
                JSON.stringify(Object.fromEntries(new FormData(adminForm)))
            );
        },

        submitJsonForm: async function(json) {
            // Set up our virtual form
            const formData = new FormData();
            formData.set('jsonForm', json);
            formData.set(Joomla.getOptions('csrf.token'), 1);
            formData.set('task', document.forms['adminForm']['task'].value);

            // Initialise the redirection detection code.
            let url = null;

            try {
                // Do an async POST to the profile config save endpoint
                const response = await fetch(
                    document.forms['adminForm'].action,
                    {
                        method: "POST",
                        body: formData,
                    });

                // Redirections will be respected by using their URL as the ultimate redirection target.
                if (response.redirected)
                {
                    url = response.url;
                }

                // Something went wrong. Reload the configuration page.
                if (!response.ok) {
                    throw new Error('Error found in network response');
                }
            } catch (error) {
                location.reload();

                return;
            }

            // No redirection URL detected; reload the page.
            if (!url)
            {
                location.reload();

                return;
            }

            // We have a redirection URL; follow it.
            window.location = url;
        }
    };
}


// =====================================================================================================================
// Initialise hooks used by the engine definitions INI files
// =====================================================================================================================

function directftp_test_connection()
{
    akeebabackup.Configuration.FtpTest.testConnection("engine.archiver.directftp.ftp_test", "engine.archiver.directftp", 0);
}

function postprocftp_test_connection()
{
    akeebabackup.Configuration.FtpTest.testConnection("engine.postproc.ftp.ftp_test", "engine.postproc.ftp", 0);
}

function directftpcurl_test_connection()
{
    akeebabackup.Configuration.FtpTest.testConnection("engine.archiver.directftpcurl.ftp_test",
        "engine.archiver.directftpcurl", 1
    );
}

function postprocftpcurl_test_connection()
{
    akeebabackup.Configuration.FtpTest.testConnection("engine.postproc.ftpcurl.ftp_test", "engine.postproc.ftpcurl", 1);
}

function directsftp_test_connection()
{
    akeebabackup.Configuration.SftpTest.testConnection("engine.archiver.directsftp.sftp_test", "engine.archiver.directsftp",
        0
    );
}

function postprocsftp_test_connection()
{
    akeebabackup.Configuration.SftpTest.testConnection("engine.postproc.sftp.sftp_test", "engine.postproc.sftp", 0);
}

function directsftpcurl_test_connection()
{
    akeebabackup.Configuration.SftpTest.testConnection("engine.archiver.directsftpcurl.sftp_test",
        "engine.archiver.directsftpcurl", 1
    );
}

function postprocsftpcurl_test_connection()
{
    akeebabackup.Configuration.SftpTest.testConnection("engine.postproc.sftpcurl.sftp_test", "engine.postproc.sftpcurl", 1);
}

function akconfig_dropbox_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=dropbox", "akeeba_dropbox_window", "width=1010,height=500,opener");
}

function akconfig_dropbox_gettoken()
{
    akeebabackup.System.AjaxURL = akeebabackup.Configuration.URLs["dpecustomapi"];

    var data = {
        engine: "dropbox",
        method: "getauth"
    };

    akeebabackup.System.doAjax(
        data,
        function (res)
        {
            if (res["error"] != "")
            {
                alert("ERROR: Could not complete authentication; please retry");
            }
            else
            {
                document.getElementById("var[engine.postproc.dropbox.token]").value        = res.token.oauth_token;
                document.getElementById("var[engine.postproc.dropbox.token_secret]").value =
                    res.token.oauth_token_secret;
                document.getElementById("var[engine.postproc.dropbox.uid]").value          = res.token.uid;
                alert("Authentication successful!");
            }
        }, function (errorMessage)
        {
            alert("ERROR: Could not complete authentication; please retry" + "\n" + errorMessage);
        }, false, 15000
    );
}

function akconfig_dropbox2_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=dropbox2", "akeeba_dropbox2_window", "width=1010,height=500,opener");
}

function akeeba_dropbox2_oauth_callback(data)
{
    // Update the tokens
    document.getElementById("var[engine.postproc.dropbox2.access_token]").value  = data.access_token;
    document.getElementById("var[engine.postproc.dropbox2.refresh_token]").value = data.refresh_token;

    // Close the window
    var myWindow = window.open("", "akeeba_dropbox2_window");
    myWindow.close();
}

function akconfig_onedrive_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=onedrive", "akeeba_onedrive_window", "width=1010,height=500,opener");
}

function akeeba_onedrive_oauth_callback(data)
{
    // Update the tokens
    document.getElementById("var[engine.postproc.onedrive.access_token]").value  = data.access_token;
    document.getElementById("var[engine.postproc.onedrive.refresh_token]").value = data.refresh_token;

    // Close the window
    var myWindow = window.open("", "akeeba_onedrive_window");
    myWindow.close();
}

function akconfig_onedrivebusiness_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=onedrivebusiness", "akeeba_onedrivebusiness_window", "width=1010,height=500,opener");
}

function akeeba_onedrivebusiness_oauth_callback(data)
{
    // Update the tokens
    document.getElementById("var[engine.postproc.onedrivebusiness.access_token]").value  = data.access_token;
    document.getElementById("var[engine.postproc.onedrivebusiness.refresh_token]").value = data.refresh_token;

    // Close the window
    var myWindow = window.open("", "akeeba_onedrivebusiness_window");
    myWindow.close();
}

function akeeba_onedrivebusiness_refreshdrives(params)
{
    if (document.getElementById('var[akeeba.advanced.postproc_engine]').value !== 'onedrivebusiness')
    {
        return;
    }

    params = params || {};

    if (typeof params["engine.postproc.onedrivebusiness.drive"] === "undefined")
    {
        params["engine.postproc.onedrivebusiness.drive"] = {
            "default": document.getElementById("var[engine.postproc.googledrive.team_drive]").value
        };
    }

    akeebabackup.System.AjaxURL = akeebabackup.Configuration.URLs["dpecustomapi"];

    var data = {
        engine: "onedrivebusiness",
        method: "getDrives",
        params: {
            "engine.postproc.onedrivebusiness.access_token":  document.getElementById(
                "var[engine.postproc.googledrive.access_token]").value,
            "engine.postproc.onedrivebusiness.refresh_token": document.getElementById(
                "var[engine.postproc.googledrive.refresh_token]").value
        }
    };

    akeebabackup.System.doAjax(
        data,
        function (res)
        {
            if (!res?.list?.length)
            {
                alert("ERROR: Could not retrieve list of OneDrive Drives.");

                return;
            }

            var dropDown       = document.getElementById("var[engine.postproc.onedrivebusiness.drive]");
            dropDown.innerHTML = "";

            res.list.forEach(item => {
                var elOption   = document.createElement("option");
                elOption.value = item[0];
                elOption.text  = item[1];

                if (params["engine.postproc.onedrivebusiness.drive"]["default"] === elOption.value)
                {
                    elOption.selected = true;
                }

                dropDown.appendChild(elOption);
            })
        }, function (errorMessage)
        {
            alert("ERROR: Could not retrieve list of OneDrive Drives. Error: " + "\n" + errorMessage);
        }, false, 15000
    );
}

function akconfig_onedriveapp_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=onedriveapp", "akeeba_onedriveapp_window", "width=1010,height=500,opener");
}

function akeeba_onedriveapp_oauth_callback(data)
{
    // Update the tokens
    document.getElementById("var[engine.postproc.onedriveapp.access_token]").value  = data.access_token;
    document.getElementById("var[engine.postproc.onedriveapp.refresh_token]").value = data.refresh_token;

    // Close the window
    var myWindow = window.open("", "akeeba_onedriveapp_window");
    myWindow.close();
}

function akconfig_googledrive_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=googledrive", "akeeba_googledrive_window", "width=1010,height=500,opener");
}

function akeeba_googledrive_oauth_callback(data)
{
    // Update the tokens
    document.getElementById("var[engine.postproc.googledrive.access_token]").value  = data.access_token;
    document.getElementById("var[engine.postproc.googledrive.refresh_token]").value = data.refresh_token;

    // Close the window
    var myWindow = window.open("", "akeeba_googledrive_window");
    myWindow.close();

    // Refresh the list of drives
    akeeba_googledrive_refreshdrives();
}

function akeeba_googledrive_refreshdrives(params)
{
    if (document.getElementById('var[akeeba.advanced.postproc_engine]').value !== 'googledrive')
    {
        return;
    }

    params = params || {};

    if (typeof params["engine.postproc.googledrive.team_drive"] === "undefined")
    {
        params["engine.postproc.googledrive.team_drive"] = {
            "default": document.getElementById("var[engine.postproc.googledrive.team_drive]").value
        };
    }

    akeebabackup.System.AjaxURL = akeebabackup.Configuration.URLs["dpecustomapi"];

    var data = {
        engine: "googledrive",
        method: "getDrives",
        params: {
            "engine.postproc.googledrive.access_token":  document.getElementById(
                "var[engine.postproc.googledrive.access_token]").value,
            "engine.postproc.googledrive.refresh_token": document.getElementById(
                "var[engine.postproc.googledrive.refresh_token]").value
        }
    };

    akeebabackup.System.doAjax(
        data,
        function (res)
        {
            console.log(res)

            if (!res?.list?.length)
            {
                alert("ERROR: Could not retrieve list of Google Drives.");

                return;
            }

            var dropDown       = document.getElementById("var[engine.postproc.googledrive.team_drive]");
            dropDown.innerHTML = "";

            res.list.forEach(item => {
                var elOption   = document.createElement("option");
                elOption.value = item[0];
                elOption.text  = item[1];

                if (params["engine.postproc.googledrive.team_drive"]["default"] === elOption.value)
                {
                    elOption.selected = true;
                }

                dropDown.appendChild(elOption);
            })
        }, function (errorMessage)
        {
            alert("ERROR: Could not retrieve list of Google Drives. Error: " + "\n" + errorMessage);
        }, false, 15000
    );
}

function akconfig_box_openoauth()
{
    var url = akeebabackup.Configuration.URLs.dpeauthopen;

    if (url.indexOf("?") == -1)
    {
        url = url + "?";
    }
    else
    {
        url = url + "&";
    }

    window.open(url + "engine=box", "akeeba_box_window", "width=1010,height=500,opener");
}

function akconfig_box_oauth_callback(data)
{
    // Update the tokens
    document.getElementById("var[engine.postproc.box.access_token]").value  = data.access_token;
    document.getElementById("var[engine.postproc.box.refresh_token]").value = data.refresh_token;

    // Close the window
    var myWindow = window.open("", "akeeba_box_window");
    myWindow.close();
}

// Initialisation
akeebabackup.System.documentReady(function() {
    // Get the configured URLs
    akeebabackup.Configuration.URLs = Joomla.getOptions("akeebabackup.Configuration.URLs", {});

    // Configuration page: we will be doing AJAX calls to the Data Processing Engine Custom API URL
    if (typeof akeebabackup.Configuration.URLs["dpecustomapi"] !== "undefined")
    {
        akeebabackup.System.params.AjaxURL = akeebabackup.Configuration.URLs["dpecustomapi"];
    }

    /**
     * The rest of the code only applies to the Configuration GUI.
     *
     * Therefore, if we have no Configuration GUI data (e.g. this file was included from a different view) we have to
     * return without trying to do anything else.
     */
    var guiData = Joomla.getOptions("akeebabackup.Configuration.GUIData", null);

    if (guiData !== null)
    {
        // Load the configuration UI data in a timeout to prevent Safari from auto-filling the password fields
        setTimeout(function ()
        {
            // Work around browsers which blatantly ignore autocomplete=off
            setTimeout(akeebabackup.Configuration.restoreDefaultPasswords, 1000);

            // Render the configuration UI in the timeout to prevent Safari from autofilling the password fields
            akeebabackup.Configuration.parseConfigData(Joomla.getOptions("akeebabackup.Configuration.GUIData", {}));

            akeebabackup.Configuration.initialisePopovers();

            // Reload drive lists if applicable
            akeeba_googledrive_refreshdrives(akeebabackup.Configuration.engines.postproc.googledrive.parameters);
            akeeba_onedrivebusiness_refreshdrives(akeebabackup.Configuration.engines.postproc.onedrivebusiness.parameters);

            // Post the form as a big JSON blob if possible
            const adminForm = document.getElementById('adminForm');

            if (adminForm) {
                adminForm.addEventListener('submit', akeebabackup.Configuration.onFormSubmit);
            }

        }, 10);
    }
});