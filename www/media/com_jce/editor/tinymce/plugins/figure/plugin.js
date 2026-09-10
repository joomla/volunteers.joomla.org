/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var VK = tinymce.VK, Node = tinymce.html.Node, each = tinymce.each, blocks = [];
    tinymce.PluginManager.add("figure", function(ed, url) {
        ed.onPreInit.add(function(ed) {
            function setClipboardData(ed, e) {
                var node, content, clipboardData = e.clipboardData;
                clipboardData && (node = ed.dom.getParent(ed.selection.getNode(), "figure")) && (ed.selection.select(node), 
                content = {
                    html: content = ed.selection.getContent({
                        contextual: !0
                    }),
                    text: content.toString()
                }, clipboardData.clearData(), clipboardData.setData("text/html", content.html), 
                clipboardData.setData("text/plain", content.text), "cut" == e.type) && ed.dom.remove(node);
            }
            ed.parser.addNodeFilter("figure", function(nodes, name) {
                for (var figcaption, node, i = nodes.length; i--; ) 0 === (node = nodes[i]).getAll("figcaption").length && ((figcaption = new Node("figcaption", 1)).attr("data-mce-empty", ed.getLang("figcaption.default", "Write a caption...")), 
                figcaption.attr("contenteditable", !0), node.append(figcaption)), 
                node.attr("data-mce-image", "1"), node.attr("contenteditable", "false"), 
                each(node.getAll("img"), function(img) {
                    img.attr("data-mce-contenteditable", "true");
                }), each(node.getAll("blockquote"), function(elm) {
                    elm.attr("contenteditable", "true");
                }), !1 !== ed.settings.figure_data_attribute && node.attr("data-wf-figure", "1");
            }), ed.parser.addNodeFilter("figcaption", function(nodes, name) {
                for (var node, i = nodes.length; i--; ) (node = nodes[i]).firstChild || node.attr("data-mce-empty", ed.getLang("figcaption.default", "Write a caption...")), 
                node.attr("contenteditable", "true");
            }), ed.serializer.addNodeFilter("figure", function(nodes, name) {
                for (var node, i = nodes.length; i--; ) (node = nodes[i]).attr("contenteditable", null), 
                each(node.getAll("img"), function(img) {
                    img.attr("data-mce-contenteditable", null);
                }), each(node.getAll("blockquote"), function(elm) {
                    elm.attr("contenteditable", null);
                });
            }), ed.serializer.addNodeFilter("figcaption", function(nodes, name) {
                for (var node, i = nodes.length; i--; ) (node = nodes[i]).firstChild ? node.attr("contenteditable", null) : node.remove();
            }), ed.serializer.addAttributeFilter("data-mce-image", function(nodes, name) {
                for (var i = nodes.length; i--; ) nodes[i].attr(name, null);
            }), each(ed.schema.getBlockElements(), function(v, k) {
                if (/\W/.test(k)) return !0;
                blocks.push(k.toLowerCase());
            }), ed.formatter.register("figure", {
                block: "figure",
                remove: "all",
                ceFalseOverride: !0,
                deep: !1,
                onformat: function(elm, fmt, vars, node) {
                    vars = vars || {}, 0 < ed.dom.select("img,video,iframe", elm).length && (ed.dom.setAttribs(elm, {
                        "data-mce-image": 1,
                        contenteditable: !1
                    }), ed.dom.setAttrib(ed.dom.select("img", elm), "data-mce-contenteditable", "true"), 
                    ed.dom.add(elm, "figcaption", {
                        "data-mce-empty": ed.getLang("figcaption.default", "Write a caption..."),
                        contenteditable: !0
                    }, vars.caption || ""), !1 !== ed.settings.figure_data_attribute) && ed.dom.setAttribs(elm, {
                        "data-wf-figure": "1"
                    });
                },
                onremove: function(node) {
                    ed.dom.remove(ed.dom.select("figcaption", node)), ed.dom.remove(ed.dom.getParent("figure", node), 1);
                }
            }), ed.formatter.register("figure_blockquote", {
                selector: "blockquote",
                onformat: function(elm, fmt, vars, node) {
                    var figure;
                    vars = vars || {}, ed.dom.getParent(elm, "figure") || (figure = ed.dom.create("figure", {
                        contenteditable: !1
                    }), elm.parentNode.insertBefore(figure, elm), figure.appendChild(elm), 
                    elm.setAttribute("contenteditable", !0), ed.dom.add(figure, "figcaption", {
                        "data-mce-empty": ed.getLang("figcaption.default", "Write a caption..."),
                        contenteditable: !0
                    }, vars.caption || ""), !1 !== ed.settings.figure_data_attribute && ed.dom.setAttrib(figure, "data-wf-figure", "1"));
                },
                onremove: function(elm) {
                    elm = ed.dom.getParent(elm, "figure");
                    elm && (ed.dom.remove(ed.dom.select("figcaption", elm)), ed.dom.remove(elm, !0));
                }
            }), ed.onBeforeExecCommand.add(function(ed, cmd, ui, v, o) {
                var parent, se = ed.selection, n = se.getNode();
                if ("FormatBlock" === cmd && "figure" === v) {
                    v = ed.dom.getParent(n, "blockquote");
                    if (v) return ed.formatter.apply("figure_blockquote", {}, v), 
                    void (o.terminate = !0);
                }
                switch (cmd) {
                  case "JustifyRight":
                  case "JustifyLeft":
                  case "JustifyCenter":
                    n && ed.dom.is(n, "img,span[data-mce-object]") && (parent = ed.dom.getParent(n, "FIGURE")) && (se.select(parent), 
                    ed.execCommand(cmd, !1), o.terminate = !0);
                }
            }), ed.onClick.add(function(ed, e) {
                "IMG" === e.target.nodeName && VK.metaKeyPressed(e) && (e = ed.dom.getParent(e.target, "figure")) && (ed.selection.select(e), 
                ed.nodeChanged());
            }), ed.onNodeChange.add(function(ed, cm, n) {
                "FIGURE" !== n.nodeName && ed.dom.removeAttrib(ed.dom.select("figure"), "data-mce-selected");
            }), ed.onCopy.add(setClipboardData), ed.onCut.add(setClipboardData), 
            ed.onKeyDown.add(function(ed, e) {
                var container, offset, isDelete = e.keyCode == VK.DELETE;
                if (!e.isDefaultPrevented() && (isDelete || e.keyCode == VK.BACKSPACE) && !VK.modifierPressed(e) && (container = (isDelete = ed.selection.getRng()).startContainer, 
                offset = isDelete.startOffset, isDelete = isDelete.collapsed, !((container = ed.dom.getParent(container, "FIGURE")) && 0 < ed.dom.select("blockquote", container).length)) && container) {
                    var node = ed.selection.getNode();
                    if ("IMG" === node.nodeName) ed.dom.remove(container), ed.nodeChanged(), 
                    e.preventDefault(); else if ("FIGCAPTION" != node.nodeName || node.nodeValue && 0 !== node.nodeValue.length || 0 !== node.childNodes.length || e.preventDefault(), 
                    3 === node.nodeType && !isDelete && !offset) {
                        var figcaption = ed.dom.getParent(node, "FIGCAPTION");
                        if (figcaption) {
                            for (;figcaption.firstChild; ) figcaption.removeChild(figcaption.firstChild);
                            e.preventDefault();
                        }
                    }
                }
            });
        });
    });
}();