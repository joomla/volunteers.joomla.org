/* This file includes original and modified code from various versions of TinyMCE, relicensed under GPL v2+ per LGPL 2.1 §3 where applicable. Source: https://github.com/widgetfactory/tinymce-muon; Copyright (c) Tiny Technologies, Inc. All rights reserved.; Copyright (c) 1999–2015 Ephox Corp. All rights reserved.; Copyright (c) 2009       Moxiecode Systems AB. All rights reserved.; Copyright (c) 2009–2025  Ryan Demmer. All rights reserved.; For a detailed history of modifications, refer to the Git commit history.; Licensed under the GNU General Public License version 2 or later (GPL v2+): https://www.gnu.org/licenses/gpl-2.0.html */
var tinymce, tinyMCEPopup = {
    init: function() {
        var self = this, win = this.getWin();
        tinymce = tinyMCE = win.tinymce, this.editor = tinymce.EditorManager.activeEditor, 
        this.params = this.editor.windowManager.params, this.features = this.editor.windowManager.features, 
        this.dom = this.editor.windowManager.createInstance("tinymce.dom.DOMUtils", document, {
            ownEvents: !0,
            proxy: tinyMCEPopup._eventProxy
        }), this.dom.bind(window, "ready", this._onDOMLoaded, this), this.listeners = [], 
        this.onInit = {
            add: function(fn, scope) {
                self.listeners.push({
                    func: fn,
                    scope: scope
                });
            }
        }, this.isWindow = !1, this.id = this.getWindowArg("mce_window_id"), this.editor.windowManager.onOpen.dispatch(this.editor.windowManager, window);
    },
    getWin: function() {
        return !window.frameElement && window.dialogArguments || opener || parent || top;
    },
    getWindowArg: function(name, defaultValue) {
        return name = this.params[name], tinymce.is(name) ? name : defaultValue;
    },
    getParam: function(name, defaultValue) {
        return this.editor.getParam(name, defaultValue);
    },
    getLang: function(name, defaultValue) {
        return this.editor.getLang(name, defaultValue);
    },
    execCommand: function(cmd, ui, val, a) {
        return (a = a || {}).skip_focus = 1, this.restoreSelection(), this.editor.execCommand(cmd, ui, val, a);
    },
    resizeToInnerSize: function() {},
    storeSelection: function() {
        this.editor.windowManager.bookmark = tinyMCEPopup.editor.selection.getBookmark(1);
    },
    restoreSelection: function() {
        !this.isWindow && tinymce.isIE && this.editor.selection.moveToBookmark(this.editor.windowManager.bookmark);
    },
    pickColor: function(e, element_id) {
        this.execCommand("mceColorPicker", !0, {
            color: document.getElementById(element_id).value,
            func: function(color) {
                document.getElementById(element_id).value = color;
                try {
                    document.getElementById(element_id).onchange();
                } catch (ex) {}
            }
        });
    },
    openBrowser: function(args) {
        tinyMCEPopup.restoreSelection(), this.editor.execCallback("file_browser_callback", args, window);
    },
    confirm: function(title, callback, scope) {
        this.editor.windowManager.confirm(title, callback, scope, window);
    },
    alert: function(title, callback, scope) {
        this.editor.windowManager.alert(title, callback, scope, window);
    },
    close: function() {
        this.editor && (this.editor.windowManager.close(window), tinymce = tinyMCE = this.editor = this.params = this.dom = this.dom.doc = null);
    },
    _restoreSelection: function(e) {
        "INPUT" != (e = e && e.target).nodeName || "submit" != e.type && "button" != e.type || tinyMCEPopup.restoreSelection();
    },
    _onDOMLoaded: function() {
        var editor = this.editor, dom = this.dom, title = document.title;
        document.body.style.display = "", this.restoreSelection(), this.resizeToInnerSize(), 
        this.isWindow ? window.focus() : editor.windowManager.setTitle(window, title), 
        tinymce.isIE || this.isWindow || dom.bind(document, "focus", function() {
            editor.windowManager.focus(this.id);
        }), tinymce.each(this.listeners, function(o) {
            o.func.call(o.scope, editor);
        }), window.focus();
    },
    _eventProxy: function(id) {
        return function(evt) {
            tinyMCEPopup.dom.events.callNativeHandler(id, evt);
        };
    }
};

tinyMCEPopup.init();