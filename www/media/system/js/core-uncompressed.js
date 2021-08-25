/**
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Only define the Joomla namespace if not defined.
Joomla = window.Joomla || {};

// Only define editors if not defined
Joomla.editors = Joomla.editors || {};

// An object to hold each editor instance on page, only define if not defined.
Joomla.editors.instances = Joomla.editors.instances || {
	/**
	 * *****************************************************************
	 * All Editors MUST register, per instance, the following callbacks:
	 * *****************************************************************
	 *
	 * getValue         Type  Function  Should return the complete data from the editor
	 *                                  Example: function () { return this.element.value; }
	 * setValue         Type  Function  Should replace the complete data of the editor
	 *                                  Example: function (text) { return this.element.value = text; }
	 * getSelection     Type  Function  Should return the selected text from the editor
	 *                                  Example: function () { return this.selectedText; }
	 * replaceSelection Type  Function  Should replace the selected text of the editor
	 *                                  If nothing selected, will insert the data at the cursor
	 *                                  Example: function (text) { return insertAtCursor(this.element, text); }
	 *
	 * USAGE (assuming that jform_articletext is the textarea id)
	 * {
	 *   To get the current editor value:
	 *      Joomla.editors.instances['jform_articletext'].getValue();
	 *   To set the current editor value:
	 *      Joomla.editors.instances['jform_articletext'].setValue('Joomla! rocks');
	 *   To replace(selection) or insert a value at  the current editor cursor:
	 *      replaceSelection: Joomla.editors.instances['jform_articletext'].replaceSelection('Joomla! rocks')
	 * }
	 *
	 * *********************************************************
	 * ANY INTERACTION WITH THE EDITORS SHOULD USE THE ABOVE API
	 * *********************************************************
	 *
	 * jInsertEditorText() @deprecated 4.0
	 */
};

(function( Joomla, document ) {
	"use strict";

	/**
	 * Generic submit form
	 *
	 * @param  {String}  task      The given task
	 * @param  {node}    form      The form element
	 * @param  {bool}    validate  The form element
	 *
	 * @returns  {void}
	 */
	Joomla.submitform = function(task, form, validate) {

		if (!form) {
			form = document.getElementById('adminForm');
		}

		if (task) {
			form.task.value = task;
		}

		// Toggle HTML5 validation
		form.noValidate = !validate;

		if (!validate) {
			form.setAttribute('novalidate', '');
		} else if ( form.hasAttribute('novalidate') ) {
			form.removeAttribute('novalidate');
		}

		// Submit the form.
		// Create the input type="submit"
		var button = document.createElement('input');
		button.style.display = 'none';
		button.type = 'submit';

		// Append it and click it
		form.appendChild(button).click();

		// If "submit" was prevented, make sure we don't get a build up of buttons
		form.removeChild(button);
	};

	/**
	 * Default function. Can be overriden by the component to add custom logic
	 *
	 * @param  {bool}  task  The given task
	 *
	 * @returns {void}
	 */
	Joomla.submitbutton = function( pressbutton ) {
		Joomla.submitform( pressbutton );
	};

	/**
	 * Custom behavior for JavaScript I18N
	 *
	 * @type {{}}
	 *
	 * Allows you to call Joomla.Text._() to get a translated JavaScript string pushed in with Text::script() in Joomla.
	 */
	Joomla.Text = {
		strings:   {},

		/**
		 * Translates a string into the current language.
		 *
		 * @param {String} key   The string to translate
		 * @param {String} def   Default string
		 *
		 * @returns {String}
		 */
		'_': function( key, def ) {

			// Check for new strings in the optionsStorage, and load them
			var newStrings = Joomla.getOptions('joomla.jtext');
			if ( newStrings ) {
				this.load(newStrings);

				// Clean up the optionsStorage from useless data
				Joomla.loadOptions({'joomla.jtext': null});
			}

			def = def === undefined ? '' : def;
			key = key.toUpperCase();

			return this.strings[ key ] !== undefined ? this.strings[ key ] : def;
		},

		/**
		 * Load new strings in to Joomla.JText
		 *
		 * @param {Object} object  Object with new strings
		 * @returns {Joomla.JText}
		 */
		load: function( object ) {
			for ( var key in object ) {
				if (!object.hasOwnProperty(key)) continue;
				this.strings[ key.toUpperCase() ] = object[ key ];
			}

			return this;
		}
	};

	/**
	 * Proxy old Joomla.JText to Joomla.Text
	 *
	 * @deprecated 5.0 Use Joomla.Text
	 */
	Joomla.JText = Joomla.Text;

	/**
	 * Joomla options storage
	 *
	 * @type {{}}
	 *
	 * @since 3.7.0
	 */
	Joomla.optionsStorage = Joomla.optionsStorage || null;

	/**
	 * Get script(s) options
	 *
	 * @param  {String}  key  Name in Storage
	 * @param  {mixed}   def  Default value if nothing found
	 *
	 * @return {mixed}
	 *
	 * @since 3.7.0
	 */
	Joomla.getOptions = function( key, def ) {
		// Load options if they not exists
		if (!Joomla.optionsStorage) {
			Joomla.loadOptions();
		}

		return Joomla.optionsStorage[key] !== undefined ? Joomla.optionsStorage[key] : def;
	};

	/**
	 * Load new options from given options object or from Element
	 *
	 * @param  {Object|undefined}  options  The options object to load. Eg {"com_foobar" : {"option1": 1, "option2": 2}}
	 *
	 * @since 3.7.0
	 */
	Joomla.loadOptions = function( options ) {
		// Load form the script container
		if (!options) {
			var elements = document.querySelectorAll('.joomla-script-options.new'),
			    str, element, option, counter = 0;

			for (var i = 0, l = elements.length; i < l; i++) {
				element = elements[i];
				str     = element.text || element.textContent;
				option  = JSON.parse(str);

				if (option) {
					Joomla.loadOptions(option);
					counter++;
				}

				element.className = element.className.replace(' new', ' loaded');
			}

			if (counter) {
				return;
			}
		}

		// Initial loading
		if (!Joomla.optionsStorage) {
			Joomla.optionsStorage = options || {};
		}
		// Merge with existing
		else if ( options ) {
			for (var p in options) {
				if (options.hasOwnProperty(p)) {
					Joomla.optionsStorage[p] = options[p];
				}
			}
		}
	};

	/**
	 * Method to replace all request tokens on the page with a new one.
	 *
	 * @param {String}  newToken  The token
	 *
	 * Used in Joomla Installation
	 */
	Joomla.replaceTokens = function( newToken ) {
		if (!/^[0-9A-F]{32}$/i.test(newToken)) { return; }

		var els = document.getElementsByTagName( 'input' ),
		    i, el, n;

		for ( i = 0, n = els.length; i < n; i++ ) {
			el = els[i];

			if ( el.type == 'hidden' && el.value == '1' && el.name.length == 32 ) {
				el.name = newToken;
			}
		}
	};

	/**
	 * USED IN: administrator/components/com_banners/views/client/tmpl/default.php
	 * Actually, probably not used anywhere. Can we deprecate in favor of <input type="email">?
	 *
	 * Verifies if the string is in a valid email format
	 *
	 * @param  {string}  text  The text for validation
	 *
	 * @return {boolean}
	 *
	 * @deprecated  4.0 No replacement. Use formvalidator
	 */
	Joomla.isEmail = function( text ) {
		console.warn('Joomla.isEmail() is deprecated, use the formvalidator instead');

		var regex = /^[\w.!#$%&‚Äô*+\/=?^`{|}~-]+@[a-z0-9-]+(?:\.[a-z0-9-]{2,})+$/i;
		return regex.test( text );
	};

	/**
	 * USED IN: all list forms.
	 *
	 * Toggles the check state of a group of boxes
	 *
	 * Checkboxes must have an id attribute in the form cb0, cb1...
	 *
	 * @param   {mixed}   checkbox  The number of box to 'check', for a checkbox element
	 * @param   {string}  stub      An alternative field name
	 *
	 * @return  {boolean}
	 */
	Joomla.checkAll = function( checkbox, stub ) {
		if (!checkbox.form) return false;

		stub = stub ? stub : 'cb';

		var c = 0,
		    i, e, n;

		for ( i = 0, n = checkbox.form.elements.length; i < n; i++ ) {
			e = checkbox.form.elements[ i ];

			if ( e.type == checkbox.type && e.id.indexOf( stub ) === 0 ) {
				e.checked = checkbox.checked;
				c += e.checked ? 1 : 0;
			}
		}

		if ( checkbox.form.boxchecked ) {
			checkbox.form.boxchecked.value = c;
		}

		return true;
	};

	/**
	 * Render messages send via JSON
	 * Used by some javascripts such as validate.js
	 * PLEASE NOTE: do NOT use user supplied input in messages as potential HTML markup is NOT sanitized!
	 *
	 * @param   {object}  messages    JavaScript object containing the messages to render. Example:
	 *                              var messages = {
	 *                                  "message": ["Message one", "Message two"],
	 *                                  "error": ["Error one", "Error two"]
	 *                              };
	 * @return  {void}
	 */
	Joomla.renderMessages = function( messages ) {
		Joomla.removeMessages();

		var messageContainer = document.getElementById( 'system-message-container' ),
		    type, typeMessages, messagesBox, title, titleWrapper, i, messageWrapper, alertClass;

		for ( type in messages ) {
			if ( !messages.hasOwnProperty( type ) ) { continue; }
			// Array of messages of this type
			typeMessages = messages[ type ];

			// Create the alert box
			messagesBox = document.createElement( 'div' );

			// Message class
			alertClass = (type === 'notice') ? 'alert-info' : 'alert-' + type;
			alertClass = (type === 'message') ? 'alert-success' : alertClass;
			alertClass = (type === 'error') ? 'alert-error alert-danger' : alertClass;

			messagesBox.className = 'alert ' + alertClass;

			// Close button
			var buttonWrapper = document.createElement( 'button' );
			buttonWrapper.setAttribute('type', 'button');
			buttonWrapper.setAttribute('data-dismiss', 'alert');
			buttonWrapper.className = 'close';
			buttonWrapper.innerHTML = '×';
			messagesBox.appendChild( buttonWrapper );

			// Title
			title = Joomla.JText._( type );

			// Skip titles with untranslated strings
			if ( typeof title != 'undefined' ) {
				titleWrapper = document.createElement( 'h4' );
				titleWrapper.className = 'alert-heading';
				titleWrapper.innerHTML = Joomla.JText._( type );
				messagesBox.appendChild( titleWrapper );
			}

			// Add messages to the message box
			for ( i = typeMessages.length - 1; i >= 0; i-- ) {
				messageWrapper = document.createElement( 'div' );
				messageWrapper.innerHTML = typeMessages[ i ];
				messagesBox.appendChild( messageWrapper );
			}

			messageContainer.appendChild( messagesBox );
		}
	};

	/**
	 * Remove messages
	 *
	 * @return  {void}
	 */
	Joomla.removeMessages = function() {
		var messageContainer = document.getElementById( 'system-message-container' );

		// Empty container with a while for Chrome performance issues
		while ( messageContainer.firstChild ) messageContainer.removeChild( messageContainer.firstChild );

		// Fix Chrome bug not updating element height
		messageContainer.style.display = 'none';
		messageContainer.offsetHeight;
		messageContainer.style.display = '';
	};

	/**
	 * Treat AJAX errors.
	 * Used by some javascripts such as sendtestmail.js and permissions.js
	 *
	 * @param   {object}  xhr         XHR object.
	 * @param   {string}  textStatus  Type of error that occurred.
	 * @param   {string}  error       Textual portion of the HTTP status.
	 *
	 * @return  {object}  JavaScript object containing the system error message.
	 *
	 * @since  3.6.0
	 */
	Joomla.ajaxErrorsMessages = function( xhr, textStatus, error ) {
		var msg = {};

		// For jQuery jqXHR
		if (textStatus === 'parsererror')
		{
			// Html entity encode.
			var encodedJson = xhr.responseText.trim();

			var buf = [];
			for (var i = encodedJson.length-1; i >= 0; i--) {
				buf.unshift( [ '&#', encodedJson[i].charCodeAt(), ';' ].join('') );
			}

			encodedJson = buf.join('');

			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_PARSE').replace('%s', encodedJson) ];
		}
		else if (textStatus === 'nocontent')
		{
			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_NO_CONTENT') ];
		}
		else if (textStatus === 'timeout')
		{
			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_TIMEOUT') ];
		}
		else if (textStatus === 'abort')
		{
			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT') ];
		}
		// For vannila XHR
		else if (xhr.responseJSON && xhr.responseJSON.message)
		{
			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_OTHER').replace('%s', xhr.status) + ' <em>' + xhr.responseJSON.message + '</em>' ];
		}
		else if (xhr.statusText)
		{
			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_OTHER').replace('%s', xhr.status) + ' <em>' + xhr.statusText + '</em>' ];
		}
		else
		{
			msg.error = [ Joomla.JText._('JLIB_JS_AJAX_ERROR_OTHER').replace('%s', xhr.status) ];
		}

		return msg;
	};

	/**
	 * USED IN: administrator/components/com_cache/views/cache/tmpl/default.php
	 * administrator/components/com_installer/views/discover/tmpl/default_item.php
	 * administrator/components/com_installer/views/update/tmpl/default_item.php
	 * administrator/components/com_languages/helpers/html/languages.php
	 * libraries/joomla/html/html/grid.php
	 *
	 * @param  {boolean}  isitchecked  Flag for checked
	 * @param  {node}     form         The form
	 *
	 * @return  {void}
	 */
	Joomla.isChecked = function( isitchecked, form ) {
		if ( typeof form  === 'undefined' ) {
			form = document.getElementById( 'adminForm' );
		}

		form.boxchecked.value = isitchecked ? parseInt(form.boxchecked.value) + 1 : parseInt(form.boxchecked.value) - 1;

		// If we don't have a checkall-toggle, done.
		if ( !form.elements[ 'checkall-toggle' ] ) return;

		// Toggle main toggle checkbox depending on checkbox selection
		var c = true, i, e, n;

		for ( i = 0, n = form.elements.length; i < n; i++ ) {
			e = form.elements[ i ];

			if ( e.type == 'checkbox' && e.name != 'checkall-toggle' && !e.checked ) {
				c = false;
				break;
			}
		}

		form.elements[ 'checkall-toggle' ].checked = c;
	};

	/**
	 * USED IN: libraries/joomla/html/toolbar/button/help.php
	 *
	 * Pops up a new window in the middle of the screen
	 *
	 * @note  This will be moved out of core.js into a new file toolbar.js in Joomla 4
	 */
	Joomla.popupWindow = function( mypage, myname, w, h, scroll ) {
		var winl = ( screen.width - w ) / 2,
		    wint = ( screen.height - h ) / 2,
		    winprops = 'height=' + h +
			    ',width=' + w +
			    ',top=' + wint +
			    ',left=' + winl +
			    ',scrollbars=' + scroll +
			    ',resizable';

		window.open( mypage, myname, winprops )
			.window.focus();
	};

	/**
	 * USED IN: libraries/joomla/html/html/grid.php
	 * In other words, on any reorderable table
	 *
	 * @param  {string}  order  The order value
	 * @param  {string}  dir    The direction
	 * @param  {string}  task   The task
	 * @param  {node}    form   The form
	 *
	 * return  {void}
	 */
	Joomla.tableOrdering = function( order, dir, task, form ) {
		if ( typeof form  === 'undefined' ) {
			form = document.getElementById( 'adminForm' );
		}

		form.filter_order.value = order;
		form.filter_order_Dir.value = dir;
		Joomla.submitform( task, form );
	};

	/**
	 * USED IN: administrator/components/com_modules/views/module/tmpl/default.php
	 *
	 * Writes a dynamically generated list
	 *
	 * @param string
	 *          The parameters to insert into the <select> tag
	 * @param array
	 *          A javascript array of list options in the form [key,value,text]
	 * @param string
	 *          The key to display for the initial state of the list
	 * @param string
	 *          The original key that was selected
	 * @param string
	 *          The original item value that was selected
	 * @param string
	 *          The elem where the list will be written
	 *
	 * @deprecated  4.0 No replacement
	 */
	window.writeDynaList = function ( selectParams, source, key, orig_key, orig_val, element ) {
		console.warn('window.writeDynaList() is deprecated without a replacement!');

		var select = document.createElement('select');
		var params = selectParams.split(' ');

		for (var l = 0; l < params.length; l++) {
			var par = params[l].split('=');

			// make sure the attribute / content can not be used for scripting
			if (par[0].trim().substr(0, 2).toLowerCase() === "on"
				|| par[0].trim().toLowerCase() === "href") {
				continue;
			}

			select.setAttribute(par[0], par[1].replace(/\"/g, ''));
		}

		var hasSelection = key == orig_key, i, selected, item;

		for (i = 0; i < source.length; i++) {
			item = source[i];

			if (item[0] != key) { continue; }

			selected = hasSelection ? orig_val == item[1] : i === 0;

			var el = document.createElement('option');
			el.setAttribute('value', item[1]);
			el.innerText = item[2];

			if (selected) {
				el.setAttribute('selected', 'selected');
			}

			select.appendChild(el);
		}

		if (element) {
			element.appendChild(select);
		} else {
			document.body.appendChild(select);
		}
	};

	/**
	 * USED IN: administrator/components/com_content/views/article/view.html.php
	 * actually, probably not used anywhere.
	 *
	 * Changes a dynamically generated list
	 *
	 * @param string
	 *          The name of the list to change
	 * @param array
	 *          A javascript array of list options in the form [key,value,text]
	 * @param string
	 *          The key to display
	 * @param string
	 *          The original key that was selected
	 * @param string
	 *          The original item value that was selected
	 *
	 * @deprecated  4.0 No replacement
	 */
	window.changeDynaList = function ( listname, source, key, orig_key, orig_val ) {
		console.warn('window.changeDynaList() is deprecated without a replacement!');

		var list = document.adminForm[ listname ],
		    hasSelection = key == orig_key,
		    i, x, item, opt;

		// empty the list
		while ( list.firstChild ) list.removeChild( list.firstChild );

		i = 0;

		for ( x in source ) {
			if (!source.hasOwnProperty(x)) { continue; }

			item = source[x];

			if ( item[ 0 ] != key ) { continue; }

			opt = new Option();
			opt.value = item[ 1 ];
			opt.text = item[ 2 ];

			if ( ( hasSelection && orig_val == opt.value ) || (!hasSelection && i === 0) ) {
				opt.selected = true;
			}

			list.options[ i++ ] = opt;
		}

		list.length = i;
	};

	/**
	 * USED IN: administrator/components/com_menus/views/menus/tmpl/default.php
	 * Probably not used at all
	 *
	 * @param radioObj
	 * @return
	 *
	 * @deprecated  4.0 No replacement
	 */
	// return the value of the radio button that is checked
	// return an empty string if none are checked, or
	// there are no radio buttons
	window.radioGetCheckedValue = function ( radioObj ) {
		console.warn('window.radioGetCheckedValue() is deprecated without a replacement!');

		if ( !radioObj ) { return ''; }

		var n = radioObj.length,
		    i;

		if ( n === undefined ) {
			return radioObj.checked ? radioObj.value : '';
		}

		for ( i = 0; i < n; i++ ) {
			if ( radioObj[ i ].checked ) {
				return radioObj[ i ].value;
			}
		}

		return '';
	};

	/**
	 * USED IN: administrator/components/com_users/views/mail/tmpl/default.php
	 * Let's get rid of this and kill it
	 *
	 * @param frmName
	 * @param srcListName
	 * @return
	 *
	 * @deprecated  4.0 No replacement
	 */
	window.getSelectedValue = function ( frmName, srcListName ) {
		console.warn('window.getSelectedValue() is deprecated without a replacement!');

		var srcList = document[ frmName ][ srcListName ],
		    i = srcList.selectedIndex;

		if ( i !== null && i > -1 ) {
			return srcList.options[ i ].value;
		} else {
			return null;
		}
	};

	/**
	 * USED IN: all over :)
	 *
	 * @param id
	 * @param task
	 * @return
	 *
	 * @deprecated 4.0  Use Joomla.listItemTask() instead
	 */
	window.listItemTask = function ( id, task ) {
		console.warn('window.listItemTask() is deprecated use Joomla.listItemTask() instead');

		return Joomla.listItemTask( id, task );
	};

	/**
	 * USED IN: all over :)
	 *
	 * @param  {string}  id    The id
	 * @param  {string}  task  The task
	 *
	 * @return {boolean}
	 */
	Joomla.listItemTask = function ( id, task ) {
		var f = document.adminForm,
		    i = 0, cbx,
		    cb = f[ id ];

		if ( !cb ) return false;

		while ( true ) {
			cbx = f[ 'cb' + i ];

			if ( !cbx ) break;

			cbx.checked = false;

			i++;
		}

		cb.checked = true;
		f.boxchecked.value = 1;
		window.submitform( task );

		return false;
	};

	/**
	 * Default function. Usually would be overriden by the component
	 *
	 * @deprecated 4.0  Use Joomla.submitbutton() instead.
	 */
	window.submitbutton = function ( pressbutton ) {
		console.warn('window.submitbutton() is deprecated use Joomla.submitbutton() instead');

		Joomla.submitbutton( pressbutton );
	};

	/**
	 * Submit the admin form
	 *
	 * @deprecated 4.0  Use Joomla.submitform() instead.
	 */
	window.submitform = function ( pressbutton ) {
		console.warn('window.submitform() is deprecated use Joomla.submitform() instead');

		Joomla.submitform(pressbutton);
	};

	// needed for Table Column ordering
	/**
	 * USED IN: libraries/joomla/html/html/grid.php
	 * There's a better way to do this now, can we try to kill it?
	 *
	 * @deprecated 4.0  No replacement
	 */
	window.saveorder = function ( n, task ) {
		console.warn('window.saveorder() is deprecated without a replacement!');

		window.checkAll_button( n, task );
	};

	/**
	 * Checks all the boxes unless one is missing then it assumes it's checked out.
	 * Weird. Probably only used by ^saveorder
	 *
	 * @param   integer  n     The total number of checkboxes expected
	 * @param   string   task  The task to perform
	 *
	 * @return  void
	 *
	 * @deprecated 4.0  No replacement
	 */
	window.checkAll_button = function ( n, task ) {
		console.warn('window.checkAll_button() is deprecated without a replacement!');

		task = task ? task : 'saveorder';

		var j, box;

		for ( j = 0; j <= n; j++ ) {
			box = document.adminForm[ 'cb' + j ];

			if ( box ) {
				box.checked = true;
			} else {
				alert( "You cannot change the order of items, as an item in the list is `Checked Out`" );
				return;
			}
		}

		Joomla.submitform( task );
	};

	/**
	 * Add Joomla! loading image layer.
	 *
	 * Used in: /administrator/components/com_installer/views/languages/tmpl/default.php
	 *          /installation/template/js/installation.js
	 *
	 * @param   {String}       task           The task to do [load, show, hide] (defaults to show).
	 * @param   {HTMLElement}  parentElement  The HTML element where we are appending the layer (defaults to body).
	 *
	 * @return  {HTMLElement}  The HTML loading layer element.
	 *
	 * @since  3.6.0
	 *
	 * @deprecated  4.0 No direct replacement.
	 *              4.0 will introduce a web component for the loading spinner, therefore the spinner will need to
	 *              explicitly be loaded in all relevant pages.
	 */
	Joomla.loadingLayer = function(task, parentElement) {
		// Set default values.
		task          = task || 'show';
		parentElement = parentElement || document.body;

		// Create the loading layer (hidden by default).
		if (task === 'load')
		{
			// Gets the site base path
			var systemPaths = Joomla.getOptions('system.paths') || {},
			    basePath    = systemPaths.root || '';

			var loadingDiv = document.createElement('div');

			loadingDiv.id = 'loading-logo';

			// The loading layer CSS styles are JS hardcoded so they can be used without adding CSS.

			// Loading layer style and positioning.
			loadingDiv.style['position']              = 'fixed';
			loadingDiv.style['top']                   = '0';
			loadingDiv.style['left']                  = '0';
			loadingDiv.style['width']                 = '100%';
			loadingDiv.style['height']                = '100%';
			loadingDiv.style['opacity']               = '0.8';
			loadingDiv.style['filter']                = 'alpha(opacity=80)';
			loadingDiv.style['overflow']              = 'hidden';
			loadingDiv.style['z-index']               = '10000';
			loadingDiv.style['display']               = 'none';
			loadingDiv.style['background-color']      = '#fff';

			// Loading logo positioning.
			loadingDiv.style['background-image']      = 'url("' + basePath + '/media/jui/images/ajax-loader.gif")';
			loadingDiv.style['background-position']   = 'center';
			loadingDiv.style['background-repeat']     = 'no-repeat';
			loadingDiv.style['background-attachment'] = 'fixed';

			parentElement.appendChild(loadingDiv);
		}
		// Show or hide the layer.
		else
		{
			if (!document.getElementById('loading-logo'))
			{
				Joomla.loadingLayer('load', parentElement);
			}

			document.getElementById('loading-logo').style['display'] = (task == 'show') ? 'block' : 'none';
		}

		return document.getElementById('loading-logo');
	};

	/**
	 * Method to Extend Objects
	 *
	 * @param  {Object}  destination
	 * @param  {Object}  source
	 *
	 * @return Object
	 */
	Joomla.extend = function (destination, source) {
		for (var p in source) {
			if (source.hasOwnProperty(p)) {
				destination[p] = source[p];
			}
		}

		return destination;
	};

	/**
	 * Method to perform AJAX request
	 *
	 * @param {Object} options   Request options:
	 * {
	 *    url:       'index.php',  // Request URL
	 *    method:    'GET',        // Request method GET (default), POST
	 *    data:      null,         // Data to be sent, see https://developer.mozilla.org/docs/Web/API/XMLHttpRequest/send
	 *    perform:   true,         // Perform the request immediately, or return XMLHttpRequest instance and perform it later
	 *    headers:   null,         // Object of custom headers, eg {'X-Foo': 'Bar', 'X-Bar': 'Foo'}
	 *
	 *    onBefore:  function(xhr){}            // Callback on before the request
	 *    onSuccess: function(response, xhr){}, // Callback on the request success
	 *    onError:   function(xhr){},           // Callback on the request error
	 * }
	 *
	 * @return XMLHttpRequest|Boolean
	 *
	 * @example
	 *
	 * 	Joomla.request({
	 *		url: 'index.php?option=com_example&view=example',
	 *		onSuccess: function(response, xhr){
	 *			console.log(response);
	 *		}
	 * 	})
	 *
	 * @see    https://developer.mozilla.org/docs/Web/API/XMLHttpRequest
	 */
	Joomla.request = function (options) {

		// Prepare the options
		options = Joomla.extend({
			url:    '',
			method: 'GET',
			data:    null,
			perform: true
		}, options);

		// Use POST for send the data
		options.method = options.data ? 'POST' : options.method.toUpperCase();

		// Set up XMLHttpRequest instance
		try{
			var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('MSXML2.XMLHTTP.3.0');

			xhr.open(options.method, options.url, true);

			// Set the headers
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			xhr.setRequestHeader('X-Ajax-Engine', 'Joomla!');

			if (options.method === 'POST') {
				var token = Joomla.getOptions('csrf.token', '');

				if (token) {
					xhr.setRequestHeader('X-CSRF-Token', token);
				}

				if (typeof(options.data) === 'string' && (!options.headers || !options.headers['Content-Type'])) {
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				}
			}

			// Custom headers
			if (options.headers){
				for (var p in options.headers) {
					if (options.headers.hasOwnProperty(p)) {
						xhr.setRequestHeader(p, options.headers[p]);
					}
				}
			}

			xhr.onreadystatechange = function () {
				// Request not finished
				if (xhr.readyState !== 4) return;

				// Request finished and response is ready
				if (xhr.status === 200) {
					if(options.onSuccess) {
						options.onSuccess.call(window, xhr.responseText, xhr);
					}
				} else if(options.onError) {
					options.onError.call(window, xhr);
				}
			};

			// Do request
			if (options.perform) {
				if (options.onBefore && options.onBefore.call(window, xhr) === false) {
					// Request interrupted
					return xhr;
				}

				xhr.send(options.data);
			}

		} catch (error) {
			window.console ? console.log(error) : null;
			return false;
		}

		return xhr;
	};

}( Joomla, document ));
