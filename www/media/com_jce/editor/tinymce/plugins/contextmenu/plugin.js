/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var Event = tinymce.dom.Event, DOM = tinymce.DOM;
    tinymce.PluginManager.add("contextmenu", function(ed) {
        var showMenu, hideMenu, self = this;
        function isContentEditable(elm) {
            return "false" !== ed.dom.getContentEditableParent(elm);
        }
        function isNativeOverrideKeyEvent(e) {
            return e.ctrlKey && !contextmenuNeverUseNative;
        }
        function isImage(elm) {
            return elm && "IMG" === elm.nodeName && isContentEditable(elm);
        }
        var contextmenuNeverUseNative = ed.settings.contextmenu_never_use_native;
        if (self.onContextMenu = new tinymce.util.Dispatcher(this), tinymce.isAndroid || tinymce.isIOS) return !1;
        function hide(ed, e) {
            e && 2 == e.button || self._menu && (self._menu.removeAll(), self._menu.destroy(), 
            Event.remove(ed.getDoc(), "click", hideMenu), self._menu = null);
        }
        hideMenu = function(e) {
            hide(ed, e);
        }, showMenu = ed.onContextMenu.add(function(ed, e) {
            var elm;
            isNativeOverrideKeyEvent(e) || (Event.cancel(e), tinymce.isMac && tinymce.isWebKit && 2 === e.button && !isNativeOverrideKeyEvent(e) && ed.selection.isCollapsed() && (isImage(e.target) || ed.selection.placeCaretAt(e.clientX, e.clientY)), 
            (isImage(e.target) || (elm = e.target) && "A" === elm.nodeName && isContentEditable(elm)) && ed.selection.select(e.target), 
            function(ed, e) {
                var m = self._menu, se = ed.selection, col = se.isCollapsed(), e = e.target;
                e && "BODY" !== e.nodeName || (e = se.getNode() || ed.getBody());
                m && (m.removeAll(), m.destroy());
                return se = DOM.getPos(ed.getContentAreaContainer()), m = ed.controlManager.createDropMenu("contextmenu", {
                    offset_x: se.x + ed.getParam("contextmenu_offset_x", 0),
                    offset_y: se.y + ed.getParam("contextmenu_offset_y", 0),
                    constrain: !0,
                    keyboard_focus: !0
                }), self._menu = m, (se = m.addMenu({
                    title: "contextmenu.align"
                })).add({
                    title: "contextmenu.left",
                    icon: "justifyleft",
                    cmd: "JustifyLeft"
                }), se.add({
                    title: "contextmenu.center",
                    icon: "justifycenter",
                    cmd: "JustifyCenter"
                }), se.add({
                    title: "contextmenu.right",
                    icon: "justifyright",
                    cmd: "JustifyRight"
                }), se.add({
                    title: "contextmenu.full",
                    icon: "justifyfull",
                    cmd: "JustifyFull"
                }), self.onContextMenu.dispatch(self, m, e, col), m;
            }(ed, e).showMenu(e.clientX || e.pageX, e.clientY || e.pageY), Event.add(ed.getDoc(), "click", hideMenu), 
            ed.nodeChanged());
        }), ed.onRemove.add(function() {
            self._menu && self._menu.removeAll();
        }), ed.onMouseDown.add(hide), ed.onKeyDown.add(hide), ed.onKeyDown.add(function(ed, e) {
            !e.shiftKey || e.ctrlKey || e.altKey || 121 !== e.keyCode || (Event.cancel(e), 
            showMenu(ed, e));
        });
    });
}();