/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var Storage = tinymce.util.Storage;
    tinymce.PluginManager.add("visualblocks", function(ed, url) {
        var state = !1;
        function toggleVisualBlocks() {
            ed.controlManager.setActive("visualblocks", state), ed.getParam("use_state_cookies", !0) && Storage.set("wf_visualblocks_state", state), 
            state ? ed.dom.addClass(ed.getBody(), "mceVisualBlocks") : ed.dom.removeClass(ed.getBody(), "mceVisualBlocks");
        }
        ed.getParam("use_state_cookies", !0) && (state = Storage.get("wf_visualblocks_state")), 
        tinymce.is(state, "string") && (state = !!(state = "null" !== state && "false" !== state ? state : !1)), 
        state = ed.getParam("visualblocks_default_state", state), ed.addCommand("mceVisualBlocks", function() {
            state = !state, toggleVisualBlocks();
        }), ed.onSetContent.add(function() {
            ed.controlManager.setActive("visualblocks", state);
        }), ed.addButton("visualblocks", {
            title: "visualblocks.desc",
            cmd: "mceVisualBlocks"
        }), ed.onInit.add(function() {
            state && toggleVisualBlocks();
        });
    });
}();