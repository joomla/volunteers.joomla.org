/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, each = tinymce.each, VK = tinymce.VK, BACKSPACE = VK.BACKSPACE, DELETE = VK.DELETE;
    tinymce.PluginManager.add("article", function(ed, url) {
        function isReadMore(n) {
            return ed.dom.is(n, "hr.mce-item-readmore");
        }
        function isPageBreak(n) {
            return ed.dom.is(n, "hr.mce-item-pagebreak");
        }
        function _cancelResize() {
            each(ed.dom.select("hr.mce-item-pagebreak, hr.mce-item-readmore"), function(n) {
                n.onresizestart = function() {
                    return !1;
                }, n.onbeforeeditfocus = function() {
                    return !1;
                };
            });
        }
        function insertBreak(s, args) {
            var h, dom = ed.dom, n = ed.selection.getNode(), blocks = "H1,H2,H3,H4,H5,H6,P,DIV,ADDRESS,PRE,FORM,TABLE,OL,UL,CAPTION,BLOCKQUOTE,CENTER,DL,DIR,FIELDSET,NOSCRIPT,NOFRAMES,MENU,ISINDEX,SAMP,SECTION,ARTICLE,HGROUP,ASIDE,FIGURE", n = dom.getParent(n, blocks, "BODY") || n, marker = (tinymce.extend(args, {
                class: "pagebreak" == s ? "mce-item-pagebreak" : "mce-item-readmore",
                "data-alt": args.alt || null
            }), args.alt = null, "readmore" == s && (args.id = "system-readmore"), 
            ed.execCommand("mceInsertContent", !1, '<span id="mce_hr_marker" data-mce-type="bookmark">\ufeff</span>', {
                skip_undo: 1
            }), dom.get("mce_hr_marker")), args = dom.create("hr", args);
            if (dom.isBlock(n)) {
                var ns, p = dom.getParent(marker, blocks, "BODY");
                if ("P" == p.nodeName || "DIV" == p.nodeName) -1 !== p.className.indexOf("wf-column") ? (blocks = dom.getParent(n, ".wf-columns"), 
                dom.insertAfter(marker, blocks)) : (dom.split(p, marker), (ns = marker.nextSibling) && ns.nodeName == p.nodeName && /^(\s|&nbsp;|\u00a00)*?$/.test(h) && dom.remove(ns)); else for (p ? "BODY" == p.parentNode.nodeName ? dom.insertAfter(marker, p) : p.parentNode.insertBefore(marker, p) : "BODY" == n.parentNode.nodeName ? dom.insertAfter(marker, n) : n.parentNode.insertBefore(marker, n), 
                p = marker.parentNode; /^(H[1-6]|ADDRESS|PRE|FORM|TABLE|OL|UL|CAPTION|BLOCKQUOTE|CENTER|DL|DIR|FIELDSET|NOSCRIPT|NOFRAMES|MENU|ISINDEX|SAMP)$/.test(p.nodeName); ) p.parentNode.insertBefore(marker, p), 
                p = marker.parentNode;
                (ns = marker.nextSibling) || (blocks = ed.getParam("forced_root_block") || "br", 
                ns = ed.dom.create(blocks), "br" != blocks && (ns.innerHTML = "\xa0"), 
                ed.dom.insertAfter(ns, marker), s = ed.selection.select(ns), ed.selection.collapse(1));
            }
            ed.dom.replace(args, marker), ed.undoManager.add();
        }
        ed.addCommand("mceReadMore", function() {
            if (ed.dom.get("system-readmore")) return alert(ed.getLang("article.readmore_alert", "There is already a Read More break inserted in this article. Only one such break is permitted. Use a Pagebreak to split the page up further.")), 
            !1;
            insertBreak("readmore", {
                id: "system-readmore"
            });
        }), ed.addCommand("mcePageBreak", function(ui, v) {
            var n = ed.selection.getNode();
            isPageBreak(n) ? function(n, v) {
                tinymce.extend(v, {
                    "data-alt": v.alt || ""
                }), v.alt = null, ed.dom.setAttribs(n, v);
            }(n, v) : insertBreak("pagebreak", v);
        }), ed.getParam("article_show_readmore", !0) && ed.addButton("readmore", {
            title: "article.readmore",
            cmd: "mceReadMore"
        }), ed.onInit.add(function() {
            ed.theme && ed.theme.onResolveName && ed.theme.onResolveName.add(function(theme, o) {
                var v, n = o.node;
                n && "HR" === n.nodeName && /mce-item-pagebreak/.test(n.className) && (v = "pagebreak"), 
                (v = n && "HR" === n.nodeName && /mce-item-readmore/.test(n.className) ? "readmore" : v) && (o.name = v);
            });
        }), ed.onNodeChange.add(function(ed, cm, n) {
            cm.setActive("readmore", isReadMore(n)), cm.setActive("pagebreak", isPageBreak(n)), 
            ed.dom.removeClass(ed.dom.select("hr.mce-item-pagebreak.mce-item-selected, hr.mce-item-readmore.mce-item-selected"), "mce-item-selected"), 
            (isPageBreak(n) || isReadMore(n)) && ed.dom.addClass(n, "mce-item-selected");
        }), ed.onBeforeSetContent.add(function(ed, o) {
            o.content = o.content.replace(/<hr(.*?) alt="([^"]+)"([^>]*?)>/gi, '<hr$1 data-alt="$2"$3>');
        }), ed.onPostProcess.add(function(ed, o) {
            o.get && (o.content = o.content.replace(/<hr(.*?)data-alt="([^"]+)"([^>]*?)>/gi, '<hr$1alt="$2"$3>'));
        }), ed.onSetContent.add(function() {
            tinymce.isIE && _cancelResize();
        }), ed.onGetContent.add(function() {
            tinymce.isIE && _cancelResize();
        }), ed.onKeyDown.add(function(ed, e) {
            var n;
            e.keyCode != BACKSPACE && e.keyCode != DELETE || (n = ed.selection.getNode(), 
            ed.dom.is(n, "hr.mce-item-pagebreak, hr.mce-item-readmore") && (ed.dom.remove(n), 
            e.preventDefault()));
        }), ed.onPreInit.add(function() {
            ed.onBeforeSetContent.addToTop(function(ed, o) {
                o.content = o.content.replace(/<hr([^>]*)\salt="([^"]+)"([^>]*)>/gi, '<hr$1 data-alt="$2"$3>');
            }), ed.parser.addNodeFilter("hr", function(nodes) {
                for (var i = 0; i < nodes.length; i++) {
                    var node = nodes[i], id = node.attr("id") || "", cls = node.attr("class") || "";
                    ("system-readmore" == id || /(mce-item|system)-pagebreak/.test(cls)) && (cls = /(mce-item|system)-pagebreak/.test(cls) ? "mce-item-pagebreak" : "mce-item-readmore", 
                    node.attr("class", cls), node.attr("alt")) && (node.attr("data-alt", node.attr("alt")), 
                    node.attr("alt", null));
                }
            }), ed.serializer.addNodeFilter("hr", function(nodes, name, args) {
                for (var i = 0; i < nodes.length; i++) {
                    var node = nodes[i], cls = node.attr("class") || "";
                    /mce-item-(pagebreak|readmore)/.test(cls) && (/mce-item-pagebreak/.test(node.attr("class")) ? node.attr("class", "system-pagebreak") : (node.attr("class", null), 
                    node.attr("id", "system-readmore")), node.attr("data-alt")) && (node.attr("alt", node.attr("data-alt")), 
                    node.attr("data-alt", null));
                }
            });
        }), ed.getParam("article_show_pagebreak", !0) && ed.addButton("pagebreak", {
            title: "article.pagebreak",
            onclick: function() {
                var html = '<div class="mceFormRow">   <label for="' + ed.id + 'article_title">' + ed.getLang("article.title", "Title") + '</label>   <div class="mceFormControl">       <input type="text" id="' + ed.id + '_article_title" autofocus />   </div></div><div class="mceFormRow">   <label for="' + ed.id + '_article_alt">' + ed.getLang("article.alias", "Alias") + '</label>   <div class="mceFormControl">       <input type="text" id="' + ed.id + '_article_alt" />   </div></div>';
                ed.windowManager.open({
                    title: ed.getLang("article.pagebreak", "PageBreak"),
                    content: html,
                    size: "mce-modal-landscape-small",
                    open: function() {
                        var label = ed.getLang("insert", "Insert"), title = DOM.get(ed.id + "_article_title"), alt = DOM.get(ed.id + "_article_alt"), o = function() {
                            var o, n = ed.selection.getNode();
                            ed.dom.is(n, "hr.mce-item-pagebreak") && (o = {
                                title: ed.dom.getAttrib(n, "title", ""),
                                alt: ed.dom.getAttrib(n, "data-alt", "")
                            });
                            return o;
                        }();
                        o && (label = ed.getLang("update", "Update"), title.value = o.title || "", 
                        alt.value = o.alt || ""), DOM.setHTML(this.id + "_insert", label), 
                        window.setTimeout(function() {
                            title.focus();
                        }, 10);
                    },
                    buttons: [ {
                        title: ed.getLang("cancel", "Cancel"),
                        id: "cancel"
                    }, {
                        title: ed.getLang("insert", "Insert"),
                        id: "insert",
                        onsubmit: function(e) {
                            var title = DOM.getValue(ed.id + "_article_title"), alt = DOM.getValue(ed.id + "_article_alt");
                            ed.execCommand("mcePageBreak", !1, {
                                title: title,
                                alt: alt
                            });
                        },
                        classes: "primary"
                    } ]
                });
            }
        });
    });
}();