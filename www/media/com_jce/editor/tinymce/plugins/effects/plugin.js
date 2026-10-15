/* jce - 2.9.99.12 | 2026-10-07 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html; see LICENSE.txt */
!function() {
    var each = tinymce.each;
    tinymce.PluginManager.add("effects", function(ed, url) {
        function getSrcSwapUrl(value) {
            value = /^\s*this\.src\s*=\s*'([^']+)';?\s*$/.exec(tinymce.trim(value));
            return value ? tinymce.trim(value[1]) : "";
        }
        function bindMouseoverEvent(ed) {
            each(ed.dom.select("img"), function(elm) {
                var src = elm.getAttribute("src");
                if (elm.onmouseover = elm.onmouseout = null, !src || !getSrcSwapUrl(elm.getAttribute("data-mce-onmouseover"))) return !0;
                elm.onmouseover = function() {
                    elm.setAttribute("src", getSrcSwapUrl(elm.getAttribute("data-mce-onmouseover")));
                }, elm.onmouseout = function() {
                    elm.setAttribute("src", getSrcSwapUrl(elm.getAttribute("data-mce-onmouseout")) || src);
                };
            });
        }
        ed.settings.allow_event_attributes && (ed.onPreInit.add(function() {
            ed.onSetContent.add(function() {
                bindMouseoverEvent(ed);
            }), ed.onUpdateMedia.add(function(ed, o) {
                bindMouseoverEvent(ed), o.before && o.after && each(ed.dom.select("img[data-mce-onmouseover]"), function(elm) {
                    each([ "data-mce-onmouseover", "data-mce-onmouseout" ], function(name) {
                        getSrcSwapUrl(elm.getAttribute(name)) === o.before && elm.setAttribute(name, "this.src='" + o.after + "';");
                    });
                });
            });
        }), this.getSrcSwapUrl = getSrcSwapUrl);
    });
}();