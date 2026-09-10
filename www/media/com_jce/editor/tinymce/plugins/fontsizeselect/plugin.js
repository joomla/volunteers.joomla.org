/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, fontSizes = [ 8, 10, 12, 14, 18, 24, 36 ];
    tinymce.PluginManager.add("fontsizeselect", function(ed, url) {
        var o, s = ed.settings;
        s.font_size_style_values || (s.font_size_style_values = "8pt,10pt,12pt,14pt,18pt,24pt,36pt"), 
        s.theme_font_sizes = ed.getParam("fontsizeselect_font_sizes", "8pt,10pt,12pt,14pt,18pt,24pt,36pt"), 
        tinymce.is(s.theme_font_sizes, "string") && (s.font_size_style_values = tinymce.explode(s.font_size_style_values), 
        s.font_size_classes = tinymce.explode(s.font_size_classes || ""), o = {}, 
        ed.settings.theme_font_sizes = s.theme_font_sizes, each(ed.getParam("theme_font_sizes", "", "hash"), function(v, k) {
            var cl;
            k == v && 1 <= v && v <= 7 && (k = v + " (" + fontSizes[v - 1] + "pt)", 
            cl = s.font_size_classes[v - 1], v = s.font_size_style_values[v - 1] || fontSizes[v - 1] + "pt"), 
            /^\s*\./.test(v) && (cl = v.replace(/\./g, "")), o[k] = cl ? {
                class: cl
            } : {
                fontSize: v
            };
        }), s.theme_font_sizes = o), ed.onNodeChange.add(function(ed, cm, n, collapsed, o) {
            var fv, cl, c = cm.get("fontsizeselect");
            c && n && each(o.parents, function(n) {
                if (n.style && (fv = n.style.fontSize || ed.dom.getStyle(n, "fontSize"), 
                cl = ed.dom.getAttrib(n, "class", ""), c.select(function(v) {
                    return !(!v.fontSize || v.fontSize !== fv) || !(!v.class || v.class !== cl) || void 0;
                }), fv)) return !1;
            });
        }), this.createControl = function(n, cf) {
            var c, i;
            if ("fontsizeselect" === n) return i = 0, (c = ed.controlManager.createListBox("fontsizeselect", {
                title: "advanced.font_size",
                onselect: function(v) {
                    var cur = c.items[c.selectedIndex];
                    !v && cur ? (cur = cur.value).class ? (ed.formatter.toggle("fontsize_class", {
                        value: cur.class
                    }), ed.undoManager.add(), ed.nodeChanged()) : ed.execCommand("FontSize", !1, cur.fontSize) : (v.class ? (ed.focus(), 
                    ed.undoManager.add(), ed.formatter.toggle("fontsize_class", {
                        value: v.class
                    }), ed.undoManager.add(), ed.nodeChanged()) : ed.execCommand("FontSize", !1, v.fontSize), 
                    c.select(function(sv) {
                        return v == sv;
                    }), cur && (cur.value.fontSize == v.fontSize || cur.value.class && cur.value.class == v.class) && c.select(null));
                }
            })) && each(ed.settings.theme_font_sizes, function(v, k) {
                var fz = v.fontSize, lh = (1 <= fz && fz <= 7 && (fz = fontSizes[parseInt(fz, 10) - 1] + "pt"), 
                Math.max(32, parseInt(fz, 10)));
                c.add(k, v, {
                    style: "font-size:" + fz + ";line-height:" + lh + "px",
                    class: "mceFontSize" + i++ + " " + (v.class || "")
                });
            }), c;
        };
    });
}();