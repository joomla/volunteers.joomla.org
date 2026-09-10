/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var Entities = tinymce.html.Entities, each = tinymce.each, extend = tinymce.extend, DomParser = tinymce.html.DomParser, HtmlSerializer = tinymce.html.Serializer, Dispatcher = tinymce.util.Dispatcher, DOM = tinymce.DOM;
    tinymce.PluginManager.add("core", function(ed, url) {
        var store;
        ed.onUpdateMedia = new Dispatcher(), ed.onWfEditorSave = new Dispatcher();
        var contentLoaded = !1, elm = ed.getElement();
        var startup_content_html = ed.settings.startup_content_html || "", quoteMap = (ed.onBeforeRenderUI.add(function() {
            if (startup_content_html && elm && !contentLoaded && ("TEXTAREA" === elm.nodeName ? "" == elm.value : "" == elm.innerHTML)) return contentLoaded = !0, 
            value = startup_content_html, (value = Entities.decode(value)) && ("TEXTAREA" === elm.nodeName ? elm.value = value : elm.innerHTML = value), 
            !0;
            var value;
        }), {
            en: {
                '"': "&ldquo;{$selection}&rdquo;",
                "'": "&lsquo;{$selection}&rsquo;"
            },
            de: {
                '"': "&bdquo;{$selection}&ldquo;",
                "'": "&sbquo;{$selection}&rsquo;"
            }
        });
        function fakeRootBlock() {
            ed.settings.editable_root = "rootblock", ed.onPreInit.addToTop(function() {
                var selection = ed.selection, dom = ed.dom;
                dom.settings.root_element = ed.settings.editable_root, ed.schema.addValidElements("#mce:root[id|data-mce-root]"), 
                ed.schema.children["mce:root"] = ed.schema.children.body, ed.schema.children.body["mce:root"] = {}, 
                ed.serializer.addAttributeFilter("data-mce-root", function(nodes) {
                    for (var i = nodes.length; i--; ) nodes[i].unwrap();
                }), ed.serializer.addAttributeFilter("data-mce-bogus", function(nodes) {
                    for (var i = nodes.length; i--; ) nodes[i].remove();
                }), ed.onBeforeSetContent.add(function(editor, o) {
                    o.content || (o.content = '<br data-mce-bogus="1">'), o.content = '<mce:root id="' + ed.settings.editable_root + '" data-mce-root="1">' + o.content + "</mce:root>";
                }), ed.onSetContent.addToTop(function(ed, o) {
                    var rng, ed = dom.get(ed.settings.editable_root);
                    ed && (/^(&nbsp;|&#160;|\s|\u00a0|)$/.test(ed.innerHTML) && (ed.innerHTML = '<br data-mce-bogus="1">'), 
                    (rng = dom.createRng()).setStart(ed, 0), rng.setEnd(ed, 0), 
                    selection.setRng(rng));
                }), ed.onSaveContent.add(function(ed, o) {
                    "&nbsp;" === o.content && (o.content = "");
                }), ed.undoManager.onBeforeAdd.add(function(um, level) {
                    var node, container = ed.dom.create("div", {}, level.content);
                    (node = container.firstChild) && 1 == node.nodeType && node.hasAttribute("data-mce-root") && (level.content = container.firstChild.innerHTML);
                });
            });
        }
        ed.onKeyUp.add(function(ed, e) {
            var map = quoteMap[ed.settings.language] || quoteMap.en;
            !e.ctrlKey || e.altKey || '"' != e.key && "'" != e.key || (map = map[e.shiftKey ? '"' : "'"], 
            ed.undoManager.add(), ed.execCommand("mceReplaceContent", !1, map));
        }), ed.onExecCommand.add(function(ed, cmd, ui, val, args) {
            "Undo" != cmd && "Redo" != cmd && "mceReApply" != cmd && "mceRepaint" != cmd && (store = {
                cmd: cmd,
                ui: ui,
                value: val,
                args: args
            });
        }), ed.addShortcut("ctrl+alt+z", "", "mceReApply"), ed.addCommand("mceReApply", function() {
            if (store && store.cmd) return ed.execCommand(store.cmd, store.ui, store.value, store.args);
        }), ed.onPreInit.add(function() {
            var pb;
            ed.onUpdateMedia.add(function(ed, o) {
                o.before && o.after && (each(ed.dom.select("img,poster"), function(elm) {
                    var after, stamp, src = elm.getAttribute("src");
                    (src = src && -1 !== src.indexOf("?") ? src.substring(0, src.indexOf("?")) : src) == o.before && (after = o.after, 
                    stamp = "?" + new Date().getTime(), -1 !== src.indexOf("?") && -1 === after.indexOf("?") && (after += stamp), 
                    ed.dom.setAttribs(elm, {
                        src: after,
                        "data-mce-src": o.after
                    })), elm.getAttribute("srcset") && function(elm, o) {
                        var srcset = elm.getAttribute("srcset");
                        if (srcset) {
                            for (var sets = srcset.split(","), i = 0; i < sets.length; i++) {
                                var values = sets[i].trim().split(" ");
                                o.before == values[0] && (values[0] = o.after), 
                                sets[i] = values.join(" ");
                            }
                            elm.setAttribute("srcset", sets.join(","));
                        }
                    }(elm, o);
                }), each(ed.dom.select("a[href]"), function(elm) {
                    ed.dom.getAttrib(elm, "href") == o.before && ed.dom.setAttribs(elm, {
                        href: o.after,
                        "data-mce-href": o.after
                    });
                }));
            }), ed.onWfEditorSave.add(function(ed, o) {
                o.content = function(ed, content) {
                    var args, parser, settings;
                    return ed.settings.validate ? (args = {
                        no_events: !0,
                        format: "html",
                        get: !0,
                        load: !0
                    }, extend(settings = {}, ed.settings), args.content = content, 
                    ed.onBeforeGetContent.dispatch(ed, args), settings.forced_root_block = !1, 
                    settings.validate = !0, parser = new DomParser(settings, ed.schema), 
                    settings = new HtmlSerializer(settings, ed.schema), args.content = settings.serialize(parser.parse(args.content), args), 
                    ed.onPostProcess.dispatch(ed, args), args.content) : content;
                }(ed, o.content);
            }), (pb = DOM.get("sp-inline-popover")) && DOM.isChildOf(ed.getElement(), pb) && ed.onGetContent.addToTop(function(ed, o) {
                var args;
                -1 != ed.id.indexOf("sppbeditor-") && "raw" == o.format && (args = tinymce.extend(o, {
                    format: "html"
                }), o.content = ed.serializer.serialize(ed.getBody(), args));
            });
        }), 0 == ed.settings.forced_root_block && 0 != ed.settings.editable_root && fakeRootBlock();
    });
}();