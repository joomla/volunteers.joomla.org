/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each;
    tinymce.PluginManager.add("effects", function(ed, url) {
        function cleanEventAttribute(val) {
            return val ? val.replace(/^\s*this.src\s*=\s*\'([^\']+)\';?\s*$/, "$1").replace(/^\s*|\s*$/g, "") : "";
        }
        function bindMouseoverEvent(ed) {
            each(ed.dom.select("img"), function(elm) {
                var src = elm.getAttribute("src"), mouseover = elm.getAttribute("data-mouseover"), mouseout = elm.getAttribute("data-mouseout");
                if (elm.onmouseover = elm.onmouseout = null, !src || !mouseover || !mouseout) return !0;
                elm.onmouseover = function() {
                    elm.setAttribute("src", elm.getAttribute("data-mouseover"));
                }, elm.onmouseout = function() {
                    elm.setAttribute("src", elm.getAttribute("data-mouseout") || src);
                };
            });
        }
        ed.onPreInit.add(function() {
            ed.onBeforeSetContent.add(function(ed, o) {
                var div;
                -1 !== o.content.indexOf("onmouseover=") && (div = ed.dom.create("div", {}, o.content), 
                each(ed.dom.select("img[onmouseover]", div), function(node) {
                    var mouseover = node.getAttribute("onmouseover"), mouseout = node.getAttribute("onmouseout");
                    return !mouseover || 0 !== mouseover.indexOf("this.src") || (mouseover = cleanEventAttribute(mouseover), 
                    node.removeAttribute("onmouseover"), !mouseover) || (node.setAttribute("data-mouseover", mouseover), 
                    void (mouseout && 0 === mouseout.indexOf("this.src") && (mouseout = cleanEventAttribute(mouseout), 
                    node.removeAttribute("onmouseout"), mouseout) && node.setAttribute("data-mouseout", mouseout)));
                }), o.content = div.innerHTML);
            }), ed.parser.addAttributeFilter("onmouseover", function(nodes) {
                for (var i = nodes.length; i--; ) {
                    var mouseover, mouseout, node = nodes[i];
                    "img" === node.name && (mouseover = node.attr("onmouseover"), 
                    mouseout = node.attr("onmouseout"), mouseover && 0 === mouseover.indexOf("this.src") && (mouseover = cleanEventAttribute(mouseover), 
                    node.attr("data-mouseover", mouseover), node.attr("onmouseover", null), 
                    mouseout) && 0 === mouseout.indexOf("this.src")) && (mouseout = cleanEventAttribute(mouseout), 
                    node.attr("data-mouseout", mouseout), node.attr("onmouseout", null));
                }
            }), ed.serializer.addAttributeFilter("data-mouseover", function(nodes) {
                for (var i = nodes.length; i--; ) {
                    var mouseout, mouseover, node = nodes[i];
                    "img" === node.name && (mouseover = node.attr("data-mouseover"), 
                    mouseout = node.attr("data-mouseout"), mouseover = cleanEventAttribute(mouseover), 
                    node.attr("data-mouseover", null), node.attr("data-mouseout", null), 
                    mouseover && (node.attr("onmouseover", "this.src='" + mouseover + "';"), 
                    mouseout = cleanEventAttribute(mouseout))) && node.attr("onmouseout", "this.src='" + mouseout + "';");
                }
            }), ed.onSetContent.add(function() {
                bindMouseoverEvent(ed);
            }), ed.onUpdateMedia.add(function(ed, o) {
                bindMouseoverEvent(ed), o.before && o.after && each(ed.dom.select("img[data-mouseover]"), function(elm) {
                    var mouseover = elm.getAttribute("data-mouseover"), mouseout = elm.getAttribute("data-mouseout");
                    if (!mouseover) return !0;
                    mouseover == o.before && elm.setAttribute("data-mouseover", o.after), 
                    mouseout == o.before && elm.setAttribute("data-mouseout", o.after);
                });
            });
        });
    });
}();