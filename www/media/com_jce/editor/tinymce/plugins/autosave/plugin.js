/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(tinymce) {
    var Dispatcher = tinymce.util.Dispatcher, Storage = window.localStorage;
    Storage && (tinymce.onBeforeUnload.add(function(tinymce, e) {
        var msg;
        tinymce.each(tinymce.editors, function(editor) {
            editor.plugins.autosave && editor.plugins.autosave.storeDraft(), !msg && editor.isDirty() && editor.settings.autosave_ask_before_unload && (msg = editor.translate("You have unsaved changes are you sure you want to navigate away?"), 
            "undefined" != typeof Joomla) && Joomla.loadingLayer && Joomla.loadingLayer("hide");
        }), msg && (e.preventDefault(), e.returnValue = msg);
    }), tinymce.PluginManager.add("autosave", function(ed) {
        var prefix, started, self = this, settings = ed.settings;
        function parseTime(time, defaultTime) {
            return ((time = /^(\d+)([ms]?)$/.exec("" + (time || defaultTime)))[2] ? {
                s: 1e3,
                m: 6e4
            }[time[2]] : 1) * parseInt(time, 10);
        }
        function hasDraft() {
            var time = parseInt(Storage.getItem(prefix + "time"), 10) || 0;
            if (!(new Date().getTime() - time > settings.autosave_retention)) return 1;
            removeDraft(!1);
        }
        function removeDraft(fire) {
            var content = Storage.getItem(prefix + "draft");
            Storage.removeItem(prefix + "draft"), Storage.removeItem(prefix + "time"), 
            !1 !== fire && content && self.onRemoveDraft.dispatch(self, {
                content: content
            });
        }
        function storeDraft() {
            var content, expires;
            !isEmpty() && ed.isDirty() && (content = ed.getContent({
                format: "raw",
                no_events: !0
            }), expires = new Date().getTime(), Storage.setItem(prefix + "draft", content), 
            Storage.setItem(prefix + "time", expires), self.onStoreDraft.dispatch(self, {
                expires: expires,
                content: content
            }));
        }
        function restoreDraft() {
            var content;
            hasDraft() && (content = Storage.getItem(prefix + "draft"), ed.setContent(content, {
                format: "raw"
            }), self.onRestoreDraft.dispatch(self, {
                content: content
            }));
        }
        function isEmpty(html) {
            var forcedRootBlockName = ed.settings.forced_root_block;
            return "" === (html = tinymce.trim(void 0 === html ? ed.getBody().innerHTML : html)) || new RegExp("^<" + forcedRootBlockName + "[^>]*>((\xa0|&nbsp;|[ \t]|<br[^>]*>)+?|)</" + forcedRootBlockName + ">|<br>$", "i").test(html);
        }
        self.onStoreDraft = new Dispatcher(self), self.onRestoreDraft = new Dispatcher(self), 
        self.onRemoveDraft = new Dispatcher(self), prefix = (prefix = (prefix = (prefix = settings.autosave_prefix || "tinymce-autosave-{path}{query}-{id}-").replace(/\{path\}/g, document.location.pathname)).replace(/\{query\}/g, document.location.search)).replace(/\{id\}/g, ed.id), 
        settings.autosave_interval = parseTime(settings.autosave_interval, "30s"), 
        settings.autosave_retention = parseTime(settings.autosave_retention, "20m"), 
        ed.addButton("autosave", {
            title: "autosave.restore_content",
            onclick: function() {
                ed.undoManager.beforeChange(), restoreDraft(), removeDraft(), ed.undoManager.add(), 
                ed.nodeChanged();
            }
        }), ed.onNodeChange.add(function() {
            var controlManager = ed.controlManager;
            controlManager.get("autosave") && controlManager.setDisabled("autosave", !hasDraft());
        }), ed.onInit.add(function() {
            ed.controlManager.get("autosave") && !started && (setInterval(function() {
                ed.removed || storeDraft();
            }, settings.autosave_interval), started = !0), !1 !== ed.settings.autosave_restore_when_empty && hasDraft() && isEmpty() && restoreDraft();
        }), ed.onSaveContent.add(function(ed, o) {
            !1 !== ed.settings.autosave_restore_when_empty && removeDraft();
        }), self.storeDraft = storeDraft;
    }));
}(tinymce);