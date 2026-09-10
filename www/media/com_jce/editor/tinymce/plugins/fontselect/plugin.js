/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each;
    tinymce.PluginManager.add("fontselect", function(ed, url) {
        ed.onNodeChange.add(function(ed, cm, n, collapsed, o) {
            var fv, c = cm.get("fontselect");
            c && n && each(o.parents, function(n) {
                if (n.style && (fv = (fv = n.style.fontFamily || ed.dom.getStyle(n, "fontFamily")).replace(/[\"\']+/g, "").replace(/^([^,]+).*/, "$1").toLowerCase(), 
                c.select(function(v) {
                    return v.replace(/^([^,]+).*/, "$1").toLowerCase() === fv;
                }), fv)) return !1;
            });
        }), this.createControl = function(n, cf) {
            if ("fontselect" === n) return ctrl = ed.controlManager.createListBox("fontselect", {
                title: "advanced.fontdefault",
                max_height: 384,
                onselect: function(v) {
                    var cur = ctrl.items[ctrl.selectedIndex];
                    !v && cur ? ed.execCommand("FontName", !1, cur.value) : (ed.execCommand("FontName", !1, v), 
                    ctrl.select(function(sv) {
                        return v == sv;
                    }), cur && cur.value == v && ctrl.select(null));
                }
            }), each(ed.getParam("fontselect_fonts", "Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats", "hash"), function(v, k) {
                /\d/.test(v) && (v = "'" + v + "'"), ctrl.add(ed.translate(k), v, {
                    style: -1 == v.indexOf("dings") ? "font-family:" + v : ""
                });
            }), ctrl;
            var ctrl;
        };
    });
}();