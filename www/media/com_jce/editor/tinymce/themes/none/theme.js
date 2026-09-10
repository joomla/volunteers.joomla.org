/* This file includes original and modified code from various versions of TinyMCE, relicensed under GPL v2+ per LGPL 2.1 §3 where applicable. Source: https://github.com/widgetfactory/tinymce-muon; Copyright (c) Tiny Technologies, Inc. All rights reserved.; Copyright (c) 1999–2015 Ephox Corp. All rights reserved.; Copyright (c) 2009       Moxiecode Systems AB. All rights reserved.; Copyright (c) 2009–2025  Ryan Demmer. All rights reserved.; For a detailed history of modifications, refer to the Git commit history.; Licensed under the GNU General Public License version 2 or later (GPL v2+): https://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM;
    tinymce.create("tinymce.themes.NoSkin", {
        init: function(ed, url) {
            var s = ed.settings;
            function grabContent() {
                var se = ed.selection, n = ed.dom.add(ed.getBody(), "div", {
                    id: "_mcePaste",
                    style: "position:absolute;left:-1000px;top:-1000px"
                }, '<br mce_bogus="1" />').firstChild, or = ed.selection.getRng(), r = ed.getDoc().createRange();
                r.setStart(n, 0), r.setEnd(n, 0), se.setRng(r), window.setTimeout(function() {
                    var h = (n = ed.dom.get("_mcePaste")).innerHTML, n = (ed.dom.remove(n), 
                    se.setRng(or), h = h.replace(/<\/?\w+[^>]*>/gi, ""), ed.dom.create("div", 0, h));
                    tinymce.each(ed.dom.select("span", n).reverse(), function(n) {
                        if (ed.dom.getAttribs(n).length <= 1 && "" === n.className) return ed.dom.remove(n, 1);
                    }), ed.execCommand("mceInsertContent", !1, ed.serializer.serialize(n, {
                        getInner: 1
                    }));
                }, 0);
            }
            (this.editor = ed).onInit.add(function() {
                ed.onBeforeExecCommand.add(function(ed, cmd, ui, val, o) {
                    return !(o.terminate = !0);
                }), ed.dom.loadCSS(url + "/skins/default/content.css");
            }), ed.onKeyDown.add(function(ed, e) {
                (e.ctrlKey && 86 == e.keyCode || e.shiftKey && 45 == e.keyCode) && grabContent();
            }), ed.onKeyDown.add(function(ed, e) {
                if (e.ctrlKey && 66 == e.keyCode || e.ctrlKey && 73 == e.keyCode || e.ctrlKey && 85 == e.keyCode) return tinymce.dom.Event.cancel(e);
            }), ed.settings.compress.css || DOM.loadCSS(s.editor_css ? ed.documentBaseURI.toAbsolute(s.editor_css) : url + "/skins/default/ui.css");
        },
        renderUI: function(o) {
            o.targetNode;
            var ic, sc, p, ed = this.editor, n = p = DOM.create("div", {
                role: "application",
                "aria-labelledby": ed.id + "_voice",
                id: ed.id + "_parent",
                class: "mceEditor mceDefaultSkin" + ("rtl" == ed.settings.directionality ? " mceRtl" : "")
            });
            return DOM.add(n, "span", {
                class: "mceVoiceLabel",
                style: "display:none;",
                id: ed.id + "_voice"
            }, ed.settings.aria_label), sc = DOM.add(n, "div", {
                role: "presentation",
                id: ed.id + "_tbl",
                class: "mceLayout"
            }), ic = DOM.add(sc, "div", {
                class: "mceIframeContainer"
            }), n = o.targetNode, DOM.insertAfter(p, n), {
                iframeContainer: ic,
                editorContainer: ed.id + "_parent",
                sizeContainer: sc,
                deltaHeight: -20
            };
        }
    }), tinymce.ThemeManager.add("none", tinymce.themes.NoSkin);
}();