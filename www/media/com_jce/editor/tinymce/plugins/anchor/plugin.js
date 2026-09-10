/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, each = tinymce.each, VK = tinymce.VK;
    tinymce.PluginManager.add("anchor", function(ed, url) {
        var self = this;
        function isAnchor(n) {
            return ed.dom.getParent(n, "a.mce-item-anchor");
        }
        function removeAnchor(e) {
            var s = ed.selection, n = s.getNode();
            !s.isCollapsed() && ed.dom.getParent(n, "a.mce-item-anchor") && (ed.undoManager.add(), 
            ed.formatter.remove("link"), e) && e.preventDefault();
        }
        function insertAnchor(v) {
            var attrib;
            if (!v) return ed.windowManager.alert("anchor.invalid"), !1;
            if (!/^[a-z][a-z0-9\-\_:\.]*$/i.test(v)) return ed.windowManager.alert("anchor.invalid"), 
            !1;
            attrib = "name", "html4" !== ed.settings.schema && (attrib = "id");
            var n = ed.selection.getNode(), at = {
                class: "mce-item-anchor"
            };
            if (n = ed.dom.getParent(n, "A")) at[attrib] = v, ed.dom.setAttribs(n, at); else {
                if (ed.dom.select("a[" + attrib + '="' + v + '"], img[data-mce-name="' + v + '"], img[id="' + v + '"]', ed.getBody()).length) return ed.windowManager.alert("anchor.exists"), 
                !1;
                ed.selection.isCollapsed() ? (at[attrib] = v, ed.execCommand("mceInsertContent", 0, ed.dom.createHTML("a", {
                    id: "__mce_tmp"
                }, "\ufeff"), {
                    skip_undo: 1
                }), n = ed.dom.get("__mce_tmp"), at.id = at.id || null, ed.dom.setAttribs(n, at), 
                ed.selection.select(n)) : (at[attrib] = v, ed.execCommand("mceInsertLink", !1, "#mce_temp_url#", {
                    skip_undo: 1
                }), at.href = at["data-mce-href"] = null, each(ed.dom.select('a[href="#mce_temp_url#"]'), function(link) {
                    ed.dom.setAttribs(link, at);
                }));
            }
            return ed.execCommand("mceEndUndoLevel"), ed.nodeChanged(), !0;
        }
        ed.settings.allow_html_in_named_anchor = !0, ed.addCommand("mceInsertAnchor", function(ui, value) {
            return insertAnchor(value);
        }), ed.onBeforeExecCommand.add(function(ed, cmd, ui, v, o) {
            var n = ed.selection.getNode();
            if ("unlink" === cmd) {
                if (isAnchor(n)) {
                    var id = n.id || n.name || "";
                    if (!id) return;
                    each(ed.dom.select("a[href]", ed.getBody()), function(node) {
                        ed.dom.getAttrib(node, "href") === "#" + id && ed.dom.remove(node, 1);
                    });
                }
                var href = ed.dom.getAttrib(n, "href");
                href && "#" === href.charAt(0) && each(ed.dom.select("a[id],a[name]", ed.getBody()), function(node) {
                    var id = node.id || node.name;
                    id && href === "#" + id && ed.dom.remove(node, 1);
                });
            }
        }), ed.onNodeChange.add(function(ed, cm, n, co) {
            var s = isAnchor(n);
            ed.dom.removeClass(ed.dom.select(".mce-item-anchor.mce-item-selected"), "mce-item-selected"), 
            cm.setActive("anchor", s), s && ed.dom.addClass(n, "mce-item-selected");
        }), ed.onKeyDown.add(function(ed, e) {
            e.keyCode !== VK.BACKSPACE && e.keyCode !== VK.DELETE || removeAnchor(e);
        }), ed.onInit.add(function() {
            ed.theme && ed.theme.onResolveName && ed.theme.onResolveName.add(function(theme, o) {
                var v, n = o.node, href = n.href;
                (v = "a" !== o.name || href && "#" != href.charAt(0) || !n.name && !n.id ? v : n.name || n.id) && (o.name = "a#" + v);
            });
        }), ed.onPreInit.add(function() {
            ed.parser.addNodeFilter("a", function(nodes) {
                for (var i = 0, len = nodes.length; i < len; i++) {
                    var node = nodes[i], cls = node.attr("class") || "";
                    !function(node) {
                        var href = node.attr("href");
                        return (node.attr("name") || node.attr("id")) && (!href || "#" === href.charAt(0) && 1 < href.length);
                    }(node) || cls && !1 !== /mce-item-anchor/.test(cls) || (cls += " mce-item-anchor", 
                    node.attr("class", tinymce.trim(cls)));
                }
            });
        }), ed.onBeforeSetContent.add(function(ed, o) {
            o.content = o.content.replace(/<a id="([^"]+)"><\/a>/gi, '<a id="$1">\ufeff</a>');
        }), ed.addButton("anchor", {
            title: "anchor.desc",
            onclick: function() {
                var html = '<div class="mceModalRow">   <label for="' + ed.id + '_anchor_input">' + ed.getLang("anchor.name", "Name") + '</label>   <div class="mceModalControl">       <input type="text" id="' + ed.id + '_anchor_input" autofocus />   </div></div>';
                ed.windowManager.open({
                    title: ed.getLang("anchor.desc", "Anchor"),
                    content: html,
                    size: "mce-modal-landscape-small",
                    open: function() {
                        var input = DOM.get(ed.id + "_anchor_input"), label = (input.value = "", 
                        ed.getLang("insert", "Insert")), n = (n = ed.selection.getNode(), 
                        n = ed.dom.getParent(n, "a.mce-item-anchor"), ed.dom.getAttrib(n, "name") || ed.dom.getAttrib(n, "id"));
                        n && (input.value = n, label = ed.getLang("update", "Update")), 
                        DOM.setHTML(this.id + "_insert", label), window.setTimeout(function() {
                            input.focus();
                        }, 10);
                    },
                    buttons: [ {
                        title: ed.getLang("anchor.remove", "Remove"),
                        id: "remove",
                        onsubmit: function(e) {
                            e.target.disabled || removeAnchor();
                        },
                        scope: self
                    }, {
                        title: ed.getLang("insert", "Insert"),
                        id: "insert",
                        onsubmit: function(e) {
                            var value = DOM.getValue(ed.id + "_anchor_input");
                            (value = value.replace(/[^\w\:\.-]/g, "")) || (e.cancelSubmit = !0), 
                            insertAnchor(value);
                        },
                        classes: "primary",
                        scope: self
                    } ]
                });
            }
        });
    });
}();