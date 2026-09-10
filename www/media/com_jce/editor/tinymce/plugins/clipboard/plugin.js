/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    "use strict";
    var DOM = tinymce.DOM, each$1 = tinymce.each;
    function openWin(ed, cmd) {
        var title = "", msg = (msg = ed.getLang("clipboard.paste_dlg_title", "Use %s+V on your keyboard to paste text into the window.")).replace(/%s/g, tinymce.isMac ? "CMD" : "CTRL"), ctrl = (title = "mcePaste" === cmd ? ed.getLang("clipboard.paste_desc") : ed.getLang("clipboard.paste_text_desc"), 
        '<textarea id="' + ed.id + '_paste_content" dir="ltr" wrap="soft" rows="14"></textarea>'), msg = '<div class="mceModalRow mceModalStack">   <label for="' + ed.id + '_paste_content">' + msg + '</label></div><div class="mceModalRow">   <div class="mceModalControl">' + ctrl + "</div></div>";
        ed.windowManager.open({
            title: title,
            content: msg,
            size: "mce-modal-portrait-xlarge",
            open: function() {
                var elm, pasteEd, inp = DOM.get(ed.id + "_paste_content");
                "mcePaste" == cmd && (elm = inp, (pasteEd = new tinymce.Editor(elm.id, {
                    plugins: "",
                    language_load: !1,
                    forced_root_block: !1,
                    verify_html: !1,
                    invalid_elements: ed.settings.invalid_elements,
                    base_url: ed.settings.base_url,
                    document_base_url: ed.settings.document_base_url,
                    directionality: ed.settings.directionality,
                    content_css: ed.settings.content_css,
                    allow_event_attributes: ed.settings.allow_event_attributes,
                    object_resizing: !1,
                    paste_upload_data_images: !0,
                    paste_data_images: !1,
                    schema: "mixed",
                    theme: function() {
                        var parent = DOM.create("div", {
                            role: "application",
                            id: elm.id + "_parent",
                            style: "width:100%"
                        }), container = DOM.add(parent, "div", {
                            style: "width:100%"
                        });
                        return DOM.insertAfter(parent, elm), {
                            iframeContainer: container,
                            editorContainer: parent
                        };
                    }
                })).contentCSS = ed.contentCSS, pasteEd.onPreInit.add(function() {
                    var dom = pasteEd.dom;
                    this.serializer.addAttributeFilter("data-mce-fragment", function(nodes, name) {
                        for (var i = nodes.length; i--; ) nodes[i].attr("data-mce-fragment", null);
                    }), pasteEd.onPastePostProcess.add(function(ed, o) {
                        each$1(dom.select("img[data-mce-upload-marker]", o.node), function(img) {
                            dom.setAttrib(img, "src", tinymce.util.Env.transparentSrc), 
                            dom.addClass(img, "mce-object mce-object-img"), dom.setStyles(img, {
                                width: img.width || "",
                                height: img.height || ""
                            });
                        });
                    }), pasteEd.onGetContent.add(function(ed, o) {
                        var node = dom.create("div", {}, o.content);
                        each$1(dom.select("img[data-mce-upload-marker]", node), function(img) {
                            dom.setAttrib(img, "src", tinymce.util.Env.transparentSrc), 
                            dom.removeClass(img, "mce-object mce-object-img"), dom.setStyles(img, {
                                width: "",
                                height: ""
                            });
                        }), o.content = node.innerHTML;
                    });
                }), pasteEd.onInit.add(function() {
                    window.setTimeout(function() {
                        pasteEd.focus();
                        var tmp = pasteEd.dom.add("br", {
                            "data-mce-bogus": "1"
                        });
                        pasteEd.selection.select(tmp), pasteEd.selection.collapse(), 
                        pasteEd.dom.remove(tmp);
                    }, 100);
                }), pasteEd.render()), window.setTimeout(function() {
                    inp.focus();
                }, 0);
            },
            close: function() {},
            buttons: [ {
                title: ed.getLang("cancel", "Cancel"),
                id: "cancel"
            }, {
                title: ed.getLang("insert", "Insert"),
                id: "insert",
                onsubmit: function(e) {
                    var content, inp = DOM.get(ed.id + "_paste_content"), data = {};
                    "mcePaste" == cmd ? (content = tinymce.get(inp.id).getContent(), 
                    content = (content = !0 !== ed.settings.code_allow_style ? content.replace(/<style[^>]*>[\s\S]+?<\/style>/gi, "") : content).replace(/<meta([^>]+)>/, ""), 
                    data.content = tinymce.trim(content), data.internal = !1) : data.text = inp.value, 
                    ed.execCommand("mceInsertClipboardContent", !1, data);
                },
                classes: "primary"
            } ]
        });
    }
    var each = tinymce.each;
    tinymce.PluginManager.add("clipboard", function(ed, url) {
        var pasteText = ed.getParam("clipboard_paste_text", 1), pasteHtml = ed.getParam("clipboard_paste_html", 1), FakeClipboard = (ed.onInit.add(function() {
            ed.plugins.contextmenu && ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                var c = ed.selection.isCollapsed();
                ed.getParam("clipboard_cut", 1) && m.add({
                    title: "advanced.cut_desc",
                    icon: "cut",
                    cmd: "Cut"
                }).setDisabled(c), ed.getParam("clipboard_copy", 1) && m.add({
                    title: "advanced.copy_desc",
                    icon: "copy",
                    cmd: "Copy"
                }).setDisabled(c), pasteHtml && m.add({
                    title: "clipboard.paste_desc",
                    icon: "paste",
                    cmd: "mcePaste"
                }), pasteText && m.add({
                    title: "clipboard.paste_text_desc",
                    icon: "pastetext",
                    cmd: "mcePasteText"
                });
            });
        }), tinymce.clipboard.FakeClipboard);
        each([ "mcePasteText", "mcePaste" ], function(cmd) {
            ed.addCommand(cmd, function(ui) {
                if (!ui || !FakeClipboard.hasData()) return openWin(ed, cmd);
                ed.execCommand("mcePasteFakeClipboard", !1, {
                    isPlainText: "mcePasteText" === cmd
                });
            });
        }), pasteHtml && ed.addButton("paste", {
            title: "clipboard.paste_desc",
            cmd: "mcePaste",
            ui: !0
        }), pasteText && ed.addButton("pastetext", {
            title: "clipboard.paste_text_desc",
            cmd: "mcePasteText",
            ui: !0
        }), ed.getParam("clipboard_cut", 1) && ed.addButton("cut", {
            title: "advanced.cut_desc",
            cmd: "Cut",
            icon: "cut"
        }), ed.getParam("clipboard_copy", 1) && ed.addButton("copy", {
            title: "advanced.copy_desc",
            cmd: "Copy",
            icon: "copy"
        });
    });
}();