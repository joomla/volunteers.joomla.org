/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.akeebabackup = window.akeebabackup || {};

window.akeebabackup.Ajax = window.akeebabackup.Ajax || {
    // Maps nonsense HTTP status codes to what should actually be returned
    xhrSuccessStatus: {
        // File protocol always yields status code 0, assume 200
        0: 200,
        // Support: IE <=9 only. Sometimes IE returns 1223 when it should be 204
        1223: 204
    },

    // Used for chained AJAX: each request will be launched once the previous one is done (successfully or not)
    requestArray:    [],
    processingQueue: false,
    /**
     * Performs an asynchronous AJAX request. Mostly compatible with jQuery 1.5+ calling conventions, or at least the
     * subset
     * of the features we used in our software.
     *
     * The parameters can be
     * method        string      HTTP method (GET, POST, PUT, ...). Default: POST.
     * url        string      URL to access over AJAX. Required.
     * timeout    int         Request timeout in msec. Default: 600,000 (ten minutes)
     * data        object      Data to send to the AJAX URL. Default: empty
     * success    function    function(string responseText, string responseStatus, XMLHttpRequest xhr)
     * error        function    function(XMLHttpRequest xhr, string errorType, Exception e)
     * beforeSend    function    function(XMLHttpRequest xhr, object parameters) You can modify xhr, not parameters.
     * Return false to abort the request.
     *
     * @param   url         {string}  URL to send the AJAX request to
     * @param   parameters  {object}  Configuration parameters
     */
    ajax:
        function (url, parameters)
        {
            // Handles jQuery 1.0 calling style of .ajax(parameters), passing the URL as a property of the parameters
            // object
            if (typeof (parameters) == "undefined")
            {
                parameters = url;
                url        = parameters.url;
            }

            // Get the parameters I will use throughout
            var method          = (typeof (parameters.type) == "undefined") ? "POST" : parameters.type;
            method              = method.toUpperCase();
            var data            = (typeof (parameters.data) == "undefined") ? {} : parameters.data;
            var sendData        = null;
            var successCallback = (typeof (parameters.success) == "undefined") ? null : parameters.success;
            var errorCallback   = (typeof (parameters.error) == "undefined") ? null : parameters.error;

            // === Cache busting
            var cache = (typeof (parameters.cache) == "undefined") ? false : parameters.url;

            if (!cache)
            {
                var now                = new Date().getTime() / 1000;
                var s                  = parseInt(now, 10);
                data._cacheBustingJunk = Math.round((now - s) * 1000) / 1000;
            }

            // === Interpolate the data
            if ((method === "POST") || (method === "PUT"))
            {
                sendData = this.interpolateParameters(data);
            }
            else
            {
                url += url.indexOf("?") === -1 ? "?" : "&";
                url += this.interpolateParameters(data);
            }

            // === Get the XHR object
            var xhr = new XMLHttpRequest();
            xhr.open(method, url);

            // === Handle POST / PUT data
            if ((method === "POST") || (method === "PUT"))
            {
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            }

            // --- Set the load handler
            xhr.onload = function (event)
            {
                var status         = akeebabackup.Ajax.xhrSuccessStatus[xhr.status] || xhr.status;
                var statusText     = xhr.statusText;
                var isBinaryResult = (xhr.responseType || "text") !== "text" || typeof xhr.responseText !== "string";
                var responseText   = isBinaryResult ? xhr.response : xhr.responseText;
                var headers        = xhr.getAllResponseHeaders();

                if (status === 200)
                {
                    if (successCallback != null)
                    {
                        akeebabackup.Ajax.triggerCallbacks(successCallback, responseText, statusText, xhr);
                    }

                    return;
                }

                if (errorCallback)
                {
                    akeebabackup.Ajax.triggerCallbacks(errorCallback, xhr, "error", null);
                }
            };

            // --- Set the error handler
            xhr.onerror = function (event)
            {
                if (errorCallback)
                {
                    akeebabackup.Ajax.triggerCallbacks(errorCallback, xhr, "error", null);
                }
            };

            // IE 8 is a pain the butt
            if (window.attachEvent && !window.addEventListener)
            {
                xhr.onreadystatechange = function ()
                {
                    if (this.readyState === 4)
                    {
                        var status = akeebabackup.Ajax.xhrSuccessStatus[this.status] || this.status;

                        if (status >= 200 && status < 400)
                        {
                            // Success!
                            xhr.onload();
                        }
                        else
                        {
                            xhr.onerror();
                        }
                    }
                };
            }

            // --- Set the timeout handler
            xhr.ontimeout = function ()
            {
                if (errorCallback)
                {
                    akeebabackup.Ajax.triggerCallbacks(errorCallback, xhr, "timeout", null);
                }
            };

            // --- Set the abort handler
            xhr.onabort = function ()
            {
                if (errorCallback)
                {
                    akeebabackup.Ajax.triggerCallbacks(errorCallback, xhr, "abort", null);
                }
            };

            // --- Apply the timeout before running the request
            var timeout = (typeof (parameters.timeout) == "undefined") ? 600000 : parameters.timeout;

            if (timeout > 0)
            {
                xhr.timeout = timeout;
            }

            // --- Call the beforeSend event handler. If it returns false the request is canceled.
            if (typeof (parameters.beforeSend) != "undefined")
            {
                if (parameters.beforeSend(xhr, parameters) === false)
                {
                    return;
                }
            }

            xhr.send(sendData);
        },

    /**
     * Adds an AJAX request to the request queue and begins processing the queue if it's not already started. The
     * request queue is a FIFO buffer. Each request will be executed as soon as the one preceeding it has completed
     * processing
     * (successfully or otherwise).
     *
     * It's the same syntax as .ajax() with the difference that the request is queued instead of executed right away.
     *
     * @param   url         {string}  The URL to send the request to
     * @param   parameters  {object}  Configuration parameters
     */
    enqueue:
        function (url, parameters)
        {
            // Handles jQuery 1.0 calling style of .ajax(parameters), passing the URL as a property of the parameters
            // object
            if (typeof (parameters) == "undefined")
            {
                parameters = url;
                url        = parameters.url;
            }

            parameters.url = url;
            akeebabackup.Ajax.requestArray.push(parameters);

            akeebabackup.Ajax.processQueue();
        },

    /**
     * Converts a simple object containing query string parameters to a single, escaped query string
     *
     * @param    object   {object}  A plain object containing the query parameters to pass
     * @param    prefix   {string}  Prefix for array-type parameters
     *
     * @returns  {string}
     *
     * @access  private
     */
    interpolateParameters:
        function (object, prefix)
        {
            prefix            = prefix || "";
            var encodedString = "";

            for (var prop in object)
            {
                if (object.hasOwnProperty(prop))
                {
                    if (encodedString.length > 0)
                    {
                        encodedString += "&";
                    }

                    if (typeof object[prop] !== "object")
                    {
                        if (prefix === "")
                        {
                            encodedString += encodeURIComponent(prop) + "=" + encodeURIComponent(object[prop]);
                        }
                        else
                        {
                            encodedString +=
                                encodeURIComponent(prefix) + "[" + encodeURIComponent(prop) + "]=" + encodeURIComponent(
                                object[prop]);
                        }

                        continue;
                    }

                    // Objects need special handling
                    encodedString += akeebabackup.Ajax.interpolateParameters(object[prop], prop);
                }
            }
            return encodedString;
        },

    /**
     * Goes through a list of callbacks and calls them in succession. Accepts a variable number of arguments.
     */
    triggerCallbacks:
        function ()
        {
            // converts arguments to real array
            var args         = Array.prototype.slice.call(arguments);
            var callbackList = args.shift();

            if (typeof (callbackList) == "function")
            {
                return callbackList.apply(null, args);
            }

            if (callbackList instanceof Array)
            {
                for (var i = 0; i < callbackList.length; i++)
                {
                    var callBack = callbackList[i];

                    if (callBack.apply(null, args) === false)
                    {
                        return false;
                    }
                }
            }

            return null;
        },

    /**
     * This helper function triggers the request queue processing using a short (50 msec) timer. This prevents a long
     * function nesting which could cause some browser to abort processing.
     *
     * @access  private
     */
    processQueueHelper:
        function ()
        {
            akeebabackup.Ajax.processingQueue = false;

            setTimeout(akeebabackup.Ajax.processQueue, 50);
        },

    /**
     * Processes the request queue
     *
     * @access  private
     */
    processQueue:
        function ()
        {
            // If I don't have any more requests reset and return
            if (!akeebabackup.Ajax.requestArray.length)
            {
                akeebabackup.Ajax.processingQueue = false;
                return;
            }

            // If I am already processing an AJAX request do nothing (I will be called again when the request
            // completes)
            if (akeebabackup.Ajax.processingQueue)
            {
                return;
            }

            // Extract the URL from the parameters
            var parameters = akeebabackup.Ajax.requestArray.shift();
            var url        = parameters.url;

            /**
             * Add our queue processing helper to the top of the success and error callback function stacks,
             * ensuring that we will process the next request in the queue as soon as the previous one
             * completes (successfully or not)
             */
            var successCallback = (typeof (parameters.success) == "undefined") ? [] : parameters.success;
            var errorCallback   = (typeof (parameters.error) == "undefined") ? [] : parameters.error;

            if ((typeof (successCallback) != "object") || !(successCallback instanceof Array))
            {
                successCallback = [successCallback];
            }

            if ((typeof (errorCallback) != "object") || !(errorCallback instanceof Array))
            {
                errorCallback = [errorCallback];
            }

            successCallback.unshift(akeebabackup.Ajax.processQueueHelper);
            errorCallback.unshift(akeebabackup.Ajax.processQueueHelper);

            parameters.success = successCallback;
            parameters.error   = errorCallback;

            // Mark the queue as currently being processed, blocking further requests until this one completes
            akeebabackup.Ajax.processingQueue = true;

            // Perform the actual request
            akeebabackup.Ajax.ajax(url, parameters);
        }
};

if (typeof akeebabackup.System === "undefined")
{
    akeebabackup.System = {
        notification: {
            hasDesktopNotification: false,
            iconURL:                "",

            /**
             * Requests permission for displaying desktop notifications
             */
            askPermission:
                function ()
                {
                    var hasNotification = Joomla.getOptions(
                        "akeebabackup.System.notification.hasDesktopNotification",
                        akeebabackup.System.notification.hasDesktopNotification
                    );

                    if (!hasNotification)
                    {
                        return;
                    }

                    if (window.Notification === undefined)
                    {
                        return;
                    }

                    if (window.Notification.permission === "default")
                    {
                        window.Notification.requestPermission();
                    }
                },

            /**
             * Displays a desktop notification with the given title and body content. Chrome and Firefox will display
             * our custom icon in the notification. Safari will not display our custom icon but will place the
             * notification in the iOS / Mac OS X notification centre. Firefox displays the icon to the right of the
             * notification and its own icon on the left hand side. It also plays a sound when the notification is
             * displayed. Chrome plays no sound and displays only our icon on the left hand side.
             *
             * The notifications have a default timeout of 5 seconds. Clicking on them, or waiting for 5 seconds, will
             * dismiss them. You can change the timeout using the timeout parameter. Set to 0 for a permanent
             * notification.
             *
             * @param  {String} title - The title of the notification
             * @param  {String} [bodyContent] - The body of the notification (optional)
             * @param  {Number} [timeout=5000] - Notification timeout in milliseconds
             */
            notify:
                function (title, bodyContent, timeout)
                {
                    if (window.Notification === undefined)
                    {
                        return;
                    }

                    if (window.Notification.permission !== "granted")
                    {
                        return;
                    }

                    if (timeout === undefined)
                    {
                        timeout = 5000;
                    }

                    if (bodyContent === undefined)
                    {
                        bodyContent = "";
                    }

                    var n = new window.Notification(title, {
                        "body": bodyContent,
                        "icon": Joomla.getOptions(
                            "akeebabackup.System.notification.iconURL", akeebabackup.System.notification.iconURL)
                    });

                    if (timeout > 0)
                    {
                        setTimeout(function (notification)
                        {
                            return function ()
                            {
                                notification.close();
                            }
                        }(n), timeout);
                    }
                }
        },

        params: {
            AjaxURL:              "",
            errorCallback:        null,
            password:             "",
            errorDialogId:        "errorDialog",
            errorDialogMessageId: "errorDialogPre"
        },

        /**
         * Find an HTML element given an HTML element object or element ID
         *
         * @param {string|Element} element
         *
         * @return {Element|null}
         */
        findElement:
            function (element)
            {
                if (typeof element === "undefined")
                {
                    return null;
                }

                if (element === null)
                {
                    return null;
                }

                // Allow the passing of an element ID string instead of the DOM elem
                if (typeof element === "string")
                {
                    element = document.getElementById(element);
                }

                if (typeof element !== "object")
                {
                    return null;
                }

                if (!(element instanceof Element))
                {
                    return null;
                }

                return element;
            },

        /**
         * An extremely simple error handler, dumping error messages to screen
         *
         * @param  {String} error - The error message string
         */
        defaultErrorHandler:
            function (error)
            {
                if ((error == null) || (typeof error == "undefined"))
                {
                    return;
                }

                alert("An error has occurred\n" + error);
            },

        /**
         * An error handler displayed in a Modal dialog. It requires you to set up a modal dialog div with id
         * "errorDialog"
         *
         * @param  {String} error - The error message string
         */
        modalErrorHandler:
            function (error)
            {
                var dialogId       = Joomla.getOptions(
                    "akeebabackup.System.params.errorDialogId", akeebabackup.System.params.errorDialogId);
                var errorMessageId = Joomla.getOptions(
                    "akeebabackup.System.params.errorDialogMessageId", akeebabackup.System.params.errorDialogMessageId);

                var dialogElement = document.getElementById(dialogId);
                var errorContent  = "error";

                if (dialogElement != null)
                {
                    var errorElement       = document.getElementById(errorMessageId);
                    errorElement.innerHTML = error;
                    errorContent           = dialogElement.innerHTML;
                }

                new window.bootstrap.Modal(dialogElement).show();
            },

        /**
         * Performs an AJAX request and returns the parsed JSON output.
         * akeebabackup.System.params.AjaxURL is used as the AJAX proxy URL.
         * If there is no errorCallback, the global akeebabackup.System.params.errorCallback is used.
         *
         * @param  {Object} data - An object with the query data, e.g. a serialized form
         * @param  {String} [data.ajaxURL] - The endpoint URL of the AJAX request, default
         *     akeebabackup.System.params.AjaxURL
         * @param  {Boolean} [data.useTripleHash=true] - Should we use the triple hash convention?
         * @param  {Boolean} [data.parseResponseAsJSON=true] - Should we use the triple hash convention?
         * @param  {function} successCallback - A function accepting a single object parameter, called on success
         * @param  {function} [errorCallback] - A function accepting a single string parameter, called on failure
         * @param  {Boolean} [useCaching=true] - Should we use the cache?
         * @param  {Number} [timeout=60000] - Timeout before cancelling the request in milliseconds
         */
        doAjax:
            function (data, successCallback, errorCallback, useCaching, timeout)
            {
                if (useCaching == null)
                {
                    useCaching = true;
                }

                // Add Joomla's anti-CSRF token to the request, when one is available. This lets the backend tasks
                // invoked through doAjax enforce $this->checkToken() against cross-site request forgery. The token is
                // only present on pages which set it up (i.e. the backend), so guest/front-end pages are unaffected.
                var csrfToken = Joomla.getOptions("csrf.token", "");

                if (csrfToken)
                {
                    data[csrfToken] = 1;
                }

                // We always want to burst the cache
                var now                = new Date().getTime() / 1000;
                var s                  = parseInt(String(now), 10);
                data._cacheBustingJunk = Math.round((now - s) * 1000) / 1000;

                if (timeout == null)
                {
                    timeout = 600000;
                }

                var url = Joomla.getOptions("akeebabackup.System.params.AjaxURL", akeebabackup.System.params.AjaxURL);

                // Override the AJAX URL
                if (data.hasOwnProperty("ajaxURL"))
                {
                    url = data.ajaxURL;

                    delete data.url;
                }

                // Should I expect triple hashes before and after the JSON message?
                var useTripleHash = true;

                if (data.hasOwnProperty("useTripleHash"))
                {
                    useTripleHash = data.useTripleHash;

                    delete data.useTripleHash;
                }

                // Should I parse the response as JSON?
                var parseResponseAsJSON = true;

                if (data.hasOwnProperty("parseResponseAsJSON"))
                {
                    parseResponseAsJSON = data.parseResponseAsJSON;

                    delete data.parseResponseAsJSON;
                }

                // Set up an error response callback
                if (errorCallback == null)
                {
                    errorCallback =
                        Joomla.getOptions(
                            "akeebabackup.System.params.errorCallback",
                            akeebabackup.System.params.errorCallback || akeebabackup.System.modalErrorHandler
                        );
                }

                if (errorCallback == null)
                {
                    errorCallback = akeebabackup.System.defaultErrorHandler;
                }

                var structure =
                        {
                            type:    "POST",
                            url:     url,
                            cache:   false,
                            data:    data,
                            timeout: timeout,
                            success: function (msg)
                                     {
                                         // Initialize
                                         var message = "";

                                         if (useTripleHash)
                                         {
                                             // Get rid of junk before the data
                                             var valid_pos = msg.indexOf("###");

                                             if (valid_pos === -1)
                                             {
                                                 // Valid data not found in the response
                                                 msg = akeebabackup.System.sanitizeErrorMessage(msg);
                                                 msg = "Invalid AJAX data: " + msg;

                                                 errorCallback(msg);

                                                 return;
                                             }
                                         }

                                         message = msg;

                                         if (useTripleHash)
                                         {
                                             if (valid_pos !== 0)
                                             {
                                                 // Data is prefixed with junk; remove the junk
                                                 message = msg.substr(valid_pos);
                                             }

                                             // Remove triple hash in the beginning
                                             message = message.substr(3);

                                             // Get of rid of junk after the data
                                             valid_pos = message.lastIndexOf("###");
                                             // Remove triple hash in the end
                                             message   = message.substr(0, valid_pos);
                                         }

                                         try
                                         {
                                             var data = JSON.parse(message);
                                         }
                                         catch (err)
                                         {
                                             message = akeebabackup.System.sanitizeErrorMessage(message);
                                             msg     = err.message + "\n<br/>\n<pre>\n" + message + "\n</pre>";

                                             errorCallback(msg);

                                             return;
                                         }

                                         // Call the callback function
                                         successCallback(data);
                                     },
                            error:   function (Request, textStatus, errorThrown)
                                     {
                                         var text    = Request.responseText ? Request.responseText : "";
                                         var message = "<strong>AJAX Loading Error</strong><br/>HTTP Status: " + Request.status +
                                             " (" + Request.statusText + ")<br/>";

                                         message = message + "Internal status: " + textStatus + "<br/>";
                                         message = message + "XHR ReadyState: " + Request.readyState + "<br/>";
                                         message =
                                             message + "Raw server response:<br/>" + akeebabackup.System.sanitizeErrorMessage(
                                             text);

                                         errorCallback(message);
                                     }
                        };

                // Should I issue an enqueued AJAX call?
                if (useCaching)
                {
                    akeebabackup.Ajax.enqueue(structure);

                    return;
                }

                akeebabackup.Ajax.ajax(structure);
            },

        /**
         * Sanitize a message before displaying it in an error dialog. Some servers return an HTML page with DOM
         * modifying JavaScript when they block the backup script for any reason (usually with a 5xx HTTP error code).
         * Displaying the raw response in the error dialog has the side-effect of killing our backup resumption
         * JavaScript or even completely destroy the page, making backup restart impossible.
         *
         * @param {String} msg - The message to sanitize
         *
         * @returns {String}
         */
        sanitizeErrorMessage:
            function (msg)
            {
                if (msg.indexOf("<script") > -1)
                {
                    try
                    {
                        msg = (new DOMParser().parseFromString(msg ?? "", "text/html")).textContent;
                    }
                    catch (e)
                    {
                        msg = "(HTML containing script tags)";
                    }
                }

                return msg;
            },

        /**
         * Adds an event listener to an element
         *
         * @param {Element|String} element - The element or DOM ID to set the event listener to
         * @param {String} eventName - The name of the event to handle, e.g. "click", "change", "error", ...
         * @param {function} listener - The event listener to add
         */
        addEventListener:
            function (element, eventName, listener)
            {
                element = akeebabackup.System.findElement(element);

                if (!element)
                {
                    return;
                }

                element.addEventListener(eventName, listener);
            },

        /**
         * Remove an event listener from an element
         *
         * @param {Element|String} element - The element or DOM ID to remove the event listener from
         * @param {String} eventName - The name of the event to handle, e.g. "click", "change", "error", ...
         * @param {function} listener - The event listener to remove
         */
        removeEventListener:
            function (element, eventName, listener)
            {
                element = akeebabackup.System.findElement(element);

                if (!element)
                {
                    return;
                }

                element.removeEventListener(eventName, listener);
            },

        /**
         * Trigger an event on a DOM element
         *
         * @param {Element|String} element - The element or DOM ID to trigger the event on
         * @param {String} eventName - The name of the event to trigger, e.g. "click", "change", "error", ...
         */
        triggerEvent:
            function (element, eventName)
            {
                element = akeebabackup.System.findElement(element);

                if (!element)
                {
                    return;
                }

                var event = null;

                event = document.createEvent("Event");
                event.initEvent(eventName, true, true);
                element.dispatchEvent(event);
            },

        documentReady:
            function (callback, context)
            {
            },

        /**
         * Apply a callback to a list of DOM elements
         *
         * This is useful when applying an event handler to all objects that have a specific CSS class. Example:
         * akeebabackup.System.iterateNodes("superClickable", function (el) {
         *     akeebabackup.System.addEventListener(el, "click", mySuperClickableHandler);
         * });
         *
         * @param {String|NodeList} elements - The NodeList to iterate or a CSS query selector pass to
         *     document.querySelectorAll
         * @param {function} callback - The callback to execute for each node
         * @param {*} [context] - Optional additional parameter to pass to the callback
         */
        iterateNodes:
            function (elements, callback, context)
            {
                if (typeof callback != "function")
                {
                    return;
                }

                // Allow passing a CSS selector string instead of a NodeList object
                if (typeof elements === "string")
                {
                    elements = document.querySelectorAll(elements);
                }

                if (elements.length === 0)
                {
                    return;
                }

                var i;
                var el;

                for (i = 0; i < elements.length; i++)
                {
                    el = elements[i];

                    if (typeof context !== "undefined")
                    {
                        callback(el, context);

                        continue;
                    }

                    callback(el);
                }
            },

        /**
         * Assign the default AJAX error handler that best matches the document.
         *
         * If the akeebabackup.System.params.errorDialogId and .errorDialogMessageId script options are set and they
         * correspond to existing elements we'll be using the modalErrorHandler. Otherwise we fall back to the dead
         * simple defaultErrorHandler that simply shows an alert.
         */
        assignDefaultErrorHandler:
            function ()
            {
                // Use the modal error handler unless there is a reason not to
                akeebabackup.System.params.errorCallback = akeebabackup.System.modalErrorHandler;

                var dialogId       = Joomla.getOptions(
                    "akeebabackup.System.params.errorDialogId", akeebabackup.System.params.errorDialogId);
                var errorMessageId = Joomla.getOptions(
                    "akeebabackup.System.params.errorDialogMessageId", akeebabackup.System.params.errorDialogMessageId);

                // If the modal configuration is not present fall back to the simpler error handler
                if ((dialogId === "") || (dialogId === null) || (errorMessageId === "") || (errorMessageId === null))
                {
                    akeebabackup.System.params.errorCallback = akeebabackup.System.defaultErrorHandler;

                    return;
                }

                // If either element used in the modal code is not present fall fall back to the simpler error handler
                var dialogElement = document.getElementById(dialogId);
                var errorElement  = document.getElementById(errorMessageId);

                if ((dialogElement === null) || (errorElement === null))
                {
                    akeebabackup.System.params.errorCallback = akeebabackup.System.defaultErrorHandler;
                }
            },

        escapeHTML: function (rawData)
                    {
                        return rawData.split("&").join("&amp;")
                                      .split("<").join("&lt;")
                                      .split(">").join("&gt;");
                    },

        /**
         * Common Events
         *
         * Replaces inline event attributes for common Joomla interactions based on the class names and data attributes
         * you add to input elements.
         *
         * * akeebaCommonEventsOnChangeSubmit. Submits a form when the change event fires. Use data-akeebasubmittarget
         * for the ID of the form to be submitted. Defaults to the standard Joomla adminForm.
         * * akeebaCommonEventsOnClickSubmit. Submits a form when the click event fires. Use data-akeebasubmittarget
         * for the ID of the form to be submitted. Defaults to the standard Joomla adminForm.
         * * akeebaCommonEventsOnClickConfirm. Shows a confirmation message on the click event. If the user accepts it
         * the click event handler proceeds as per usual. Use data-akeebaconfirmmessage to set the confirmation
         * message. An empty message results in no confirmation and the click event proceeds.
         * * akeebaCommonEventsOnChangeOrderTable. Runs Joomla.orderTable() when the change event fires. Used in browse
         * views for the sort ordering and sort field dropdowns.
         */
        CommonEvents: {
            onEventSubmit:
                function (event)
                {
                    var elChangedElement = event.currentTarget;
                    var targetString     = elChangedElement.dataset["akeebasubmittarget"] ?? "";
                    var elTarget         = document.forms.adminForm ? document.forms.adminForm : null;

                    if (targetString !== "")
                    {
                        elTarget = document.getElementById(targetString);
                    }

                    if (!elTarget)
                    {
                        return true;
                    }

                    elTarget.submit();

                    event.preventDefault();
                    return false;
                },
            onClickConfirm:
                function (event)
                {
                    var elChangedElement  = event.currentTarget;
                    var confirmLangString = elChangedElement["akeebaconfirmmessage"] ?? "";

                    if (confirmLangString === "")
                    {
                        return true;
                    }

                    var response = confirm(Joomla.Text._(confirmLangString));

                    if (response)
                    {
                        return true;
                    }

                    event.preventDefault();

                    return false;
                },
            onEventOrderTable:
                function (event)
                {
                    var elChangedElement = event.currentTarget;

                    event.preventDefault();

                    Joomla.orderTable();

                    return false;
                },
            init:
                function ()
                {
                    akeebabackup.System.iterateNodes(".akeebaCommonEventsOnChangeSubmit", function (el)
                    {
                        akeebabackup.System.addEventListener(
                            el, "change", akeebabackup.System.CommonEvents.onEventSubmit);
                    });

                    akeebabackup.System.iterateNodes(".akeebaCommonEventsOnClickSubmit", function (el)
                    {
                        akeebabackup.System.addEventListener(
                            el, "click", akeebabackup.System.CommonEvents.onEventSubmit);
                    });

                    akeebabackup.System.iterateNodes(".akeebaCommonEventsOnClickConfirm", function (el)
                    {
                        akeebabackup.System.addEventListener(
                            el, "click", akeebabackup.System.CommonEvents.onClickConfirm);
                    });

                    akeebabackup.System.iterateNodes(".akeebaCommonEventsOnChangeOrderTable", function (el)
                    {
                        akeebabackup.System.addEventListener(
                            el, "change", akeebabackup.System.CommonEvents.onEventOrderTable);
                    });
                }
        }
    }
}

/*
 Math.uuid.js (v1.4)
 http://www.broofa.com
 mailto:robert@broofa.com

 Copyright (c) 2009 Robert Kieffer
 Dual licensed under the MIT and GPL licenses.

 Usage: Math.uuid()
 */
Math.uuid = Math.uuid || (function ()
{
    // Private array of chars to use
    var CHARS = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz".split("");

    return function (len, radix)
    {
        var chars = CHARS, uuid = [];
        radix     = radix || chars.length;

        if (len)
        {
            // Compact form
            for (var i = 0; i < len; i++)
            {
                uuid[i] = chars[0 | Math.random() * radix];
            }
        }
        else
        {
            // rfc4122, version 4 form
            var r;

            // rfc4122 requires these characters
            uuid[8]  = uuid[13] = uuid[18] = uuid[23] = "-";
            uuid[14] = "4";

            // Fill in random data.  At i==19 set the high bits of clock sequence as
            // per rfc4122, sec. 4.1.5
            for (var i = 0; i < 36; i++)
            {
                if (!uuid[i])
                {
                    r       = 0 | Math.random() * 16;
                    uuid[i] = chars[(i === 19) ? (r & 0x3) | 0x8 : r];
                }
            }
        }

        return uuid.join("");
    };
})();

// Initialization
/**
 * document.ready equivalent from https://github.com/jfriend00/docReady/blob/master/docready.js
 *
 * Call as akeebabackup.System.documentReady(eventHandlerFunction)
 */
(function (funcName, baseObj)
{
    funcName = funcName || "documentReady";
    baseObj  = baseObj || akeebabackup.System;

    var readyList                   = [];
    var readyFired                  = false;
    var readyEventHandlersInstalled = false;

    /**
     * Call this when the document is ready. This function protects itself against being called more than once.
     */
    function ready()
    {
        if (!readyFired)
        {
            // This must be set to true before we start calling callbacks
            readyFired = true;

            for (var i = 0; i < readyList.length; i++)
            {
                /**
                 * If a callback here happens to add new ready handlers, this function will see that it already
                 * fired and will schedule the callback to run right after this event loop finishes so all handlers
                 * will still execute in order and no new ones will be added to the readyList while we are
                 * processing the list.
                 */
                readyList[i].fn.call(window, readyList[i].ctx);
            }

            // Allow any closures held by these functions to free
            readyList = [];
        }
    }

    /**
     * Solely for the benefit of Internet Explorer
     */
    function readyStateChange()
    {
        if (document.readyState === "complete")
        {
            ready();
        }
    }

    /**
     * This is the one public interface:
     *
     * akeeba.System.documentReady(fn, context);
     *
     * @param   {function} callback - The callback function to execute when the document is ready.
     * @param   {*} [context] - It is passed as an argument to the callback.
     */
    baseObj[funcName] = function (callback, context)
    {
        // If ready() has already fired, then just schedule the callback to fire asynchronously
        if (readyFired)
        {
            setTimeout(function ()
            {
                callback(context);
            }, 1);

            return;
        }

        // Add the function and context to the queue
        readyList.push({fn: callback, ctx: context});

        /**
         * If the document is already ready, schedule the ready() function to run immediately.
         *
         * Note: IE is only safe when the readyState is "complete", other browsers are safe when the readyState is
         * "interactive"
         */
        if (document.readyState === "complete" || (!document.attachEvent && document.readyState === "interactive"))
        {
            setTimeout(ready, 1);

            return;
        }

        // If the handlers are already installed just quit
        if (readyEventHandlersInstalled)
        {
            return;
        }

        // We don't have event handlers installed, install them
        readyEventHandlersInstalled = true;

        // -- We have an addEventListener method in the document, this is a modern browser.

        if (document.addEventListener)
        {
            // Prefer using the DOMContentLoaded event
            document.addEventListener("DOMContentLoaded", ready, false);

            // Our backup is the window's "load" event
            window.addEventListener("load", ready, false);

            return;
        }

        // -- Most likely we're stuck with an ancient version of IE

        // Our primary method of activation is the onreadystatechange event
        document.attachEvent("onreadystatechange", readyStateChange);

        // Our backup is the windows's "load" event
        window.attachEvent("onload", ready);
    }
})("documentReady", akeebabackup.System);

akeebabackup.System.documentReady(function ()
{
    // Assign the correct default error handler
    akeebabackup.System.assignDefaultErrorHandler();

    // Grid Views: click event handler for the Check All checkbox
    akeebabackup.System.iterateNodes(".akeebaGridViewCheckAll", function (el)
    {
        akeebabackup.System.addEventListener(el, "click", function ()
        {
            Joomla.checkAll(this);
        })
    });

    // Grid Views: change event handler for the ordering field and direction dropdowns
    akeebabackup.System.iterateNodes(".akeebaGridViewOrderTable", function (el)
    {
        akeebabackup.System.addEventListener(el, "change", akeebabackup.System.orderTable)
    });

    // Grid Views: change event handler for search fields which autosubmit the form on change
    akeebabackup.System.iterateNodes(".akeebaGridViewAutoSubmitOnChange", function (el)
    {
        akeebabackup.System.addEventListener(el, "change", function ()
        {
            Joomla.submitForm();
        })
    });

    // Common events initialisation
    akeebabackup.System.CommonEvents.init();
});