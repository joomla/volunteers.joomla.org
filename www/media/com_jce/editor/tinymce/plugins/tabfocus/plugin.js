/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, each = tinymce.each, explode = tinymce.explode;
    tinymce.PluginManager.add("tabfocus", function(ed, url) {
        function tabCancel(e) {
            9 !== e.keyCode || e.ctrlKey || e.altKey || e.metaKey || e.preventDefault();
        }
        function tabHandler(e) {
            var x, el, i, v;
            function find(direction) {
                function canSelect(el) {
                    return /INPUT|TEXTAREA|BUTTON/.test(el.tagName) && tinymce.get(e.id) && -1 != el.tabIndex && function canSelectRecursive(e) {
                        return "BODY" === e.nodeName || "hidden" != e.type && "none" != e.style.display && "hidden" != e.style.visibility && canSelectRecursive(e.parentNode);
                    }(el);
                }
                if (el = DOM.select(":input:enabled,*[tabindex]:not(iframe)"), each(el, function(e, i) {
                    if (e.id == ed.id) return x = i, !1;
                }), 0 < direction) {
                    for (i = x + 1; i < el.length; i++) if (canSelect(el[i])) return el[i];
                } else for (i = x - 1; 0 <= i; i--) if (canSelect(el[i])) return el[i];
                return null;
            }
            9 !== e.keyCode || e.ctrlKey || e.altKey || e.metaKey || e.isDefaultPrevented() || (1 == (v = explode(ed.getParam("tab_focus", ed.getParam("tabfocus_elements", ":prev,:next")))).length && (v[1] = v[0], 
            v[0] = ":prev"), (el = e.shiftKey ? ":prev" == v[0] ? find(-1) : DOM.get(v[0]) : ":next" == v[1] ? find(1) : DOM.get(v[1])) && (v = tinymce.get(el.id || el.name), 
            el.id && v ? v.focus() : setTimeout(function() {
                tinymce.isWebkit || window.focus(), el.focus();
            }, 10), e.preventDefault()));
        }
        ed.onKeyUp.add(tabCancel), tinymce.isGecko ? (ed.onKeyPress.add(tabHandler), 
        ed.onKeyDown.add(tabCancel)) : ed.onKeyDown.add(tabHandler);
    });
}();