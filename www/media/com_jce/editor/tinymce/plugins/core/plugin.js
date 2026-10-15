/* jce - 2.9.99.12 | 2026-10-07 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html; see LICENSE.txt */
!function() {
    var Entities = tinymce.html.Entities, each = tinymce.each, extend = tinymce.extend, HtmlSerializer = tinymce.html.Serializer, Dispatcher = tinymce.util.Dispatcher, DOM = tinymce.DOM;
    function validateContent(ed, content) {
        var args;
        return !1 === ed.settings.verify_html ? content : ((args = {
            no_events: !0,
            format: "html",
            get: !0,
            load: !0
        }).content = content, ed.onBeforeGetContent.dispatch(ed, args), content = function(ed) {
            var settings, parser;
            return ed.contentValidator || (settings = extend({}, ed.settings, {
                forced_root_block: !1,
                validate: !0
            }), (parser = ed.createParser(settings)).sanitizer.mode = null, ed.contentValidator = {
                parser: parser,
                serializer: new HtmlSerializer(settings, ed.schema)
            }), ed.contentValidator;
        }(ed), args.content = content.serializer.serialize(content.parser.parse(args.content), args), 
        ed.onPostProcess.dispatch(ed, args), args.content);
    }
    tinymce.PluginManager.add("core", function(ed, url) {
        var store;
        ed.onUpdateMedia = new Dispatcher(), ed.onWfEditorSave = new Dispatcher();
        var contentLoaded = !1, elm = ed.getElement();
        function insertContent(value) {
            return (value = Entities.decode(value)) && ("TEXTAREA" === elm.nodeName ? elm.value = value : elm.innerHTML = function(value) {
                var div = document.implementation.createHTMLDocument("").createElement("div");
                return div.innerHTML = value, each(div.querySelectorAll("script,noscript"), function(node) {
                    node.parentNode.removeChild(node);
                }), each(div.querySelectorAll("*"), function(node) {
                    for (var attrs = node.attributes, i = attrs.length - 1; 0 <= i; i--) 0 === attrs[i].name.toLowerCase().indexOf("on") && node.removeAttribute(attrs[i].name);
                }), div.innerHTML;
            }(value)), !0;
        }
        var startup_content_html = ed.settings.startup_content_html || "", quoteMap = (ed.onBeforeRenderUI.add(function() {
            if (startup_content_html && elm && !contentLoaded && ("TEXTAREA" === elm.nodeName ? "" == elm.value : "" == elm.innerHTML)) return contentLoaded = !0, 
            insertContent(startup_content_html);
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
                var container, selection = ed.selection, dom = ed.dom;
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
                    var node;
                    (container = container || document.implementation.createHTMLDocument("").createElement("div")).innerHTML = level.content, 
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
                o.before && o.after && (each(ed.dom.select("img,[poster]"), function(elm) {
                    var stamp, src = elm.getAttribute("src"), query = !1;
                    src && -1 !== src.indexOf("?") && (src = src.substring(0, src.indexOf("?")), 
                    query = !0), src == o.before && (src = o.after, stamp = "?" + new Date().getTime(), 
                    query && -1 === src.indexOf("?") && (src += stamp), ed.dom.setAttribs(elm, {
                        src: src,
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
                o.content = validateContent(ed, o.content);
            }), (pb = DOM.get("sp-inline-popover")) && DOM.isChildOf(ed.getElement(), pb) && ed.onGetContent.addToTop(function(ed, o) {
                var args;
                -1 != ed.id.indexOf("sppbeditor-") && "raw" == o.format && (args = tinymce.extend(o, {
                    format: "html"
                }), o.content = ed.serializer.serialize(ed.getBody(), args));
            });
        }), !1 === ed.settings.forced_root_block && 0 != ed.settings.editable_root && fakeRootBlock();
    });
}();