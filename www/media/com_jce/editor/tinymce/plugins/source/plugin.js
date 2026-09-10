/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, Event = tinymce.dom.Event;
    tinymce.PluginManager.add("source", function(ed, url) {
        var self = this;
        function isEditorActive() {
            return 0 == DOM.hasClass(ed.getElement(), "wf-no-editor");
        }
        function getSourceEditor() {
            return DOM.get(ed.id + "_editor_source_textarea") || null;
        }
        ed.onSetContent.add(function(ed, o) {
            self.setContent(ed.getContent(), !0);
        }), ed.onInit.add(function(ed) {
            0 != isEditorActive() && "wf-editor-source" === (ed.settings.active_tab || "") && (DOM.hide(ed.getContainer()), 
            DOM.hide(ed.getElement()), window.setTimeout(function() {
                self.toggle();
            }, 10));
        }), this.ControlManager = new tinymce.ControlManager(ed), this.setContent = function(v) {
            var editor = getSourceEditor();
            return !!editor && (editor.value = v);
        }, this.insertContent = function(v) {
            var editor = getSourceEditor();
            return editor && (editor.focus(), document.selection ? document.selection.createRange().text = v : editor.setRangeText(v, editor.selectionStart, editor.selectionEnd, "end")), 
            !1;
        }, this.getContent = function() {
            var editor = getSourceEditor();
            return editor ? editor.value : null;
        }, this.hide = function() {
            DOM.hide(ed.id + "_editor_source");
        }, this.isHidden = function() {
            return DOM.isHidden(ed.id + "_editor_source");
        }, this.save = function(content, debounced) {
            var args, el = ed.getElement();
            return el ? (args = {
                content: content = tinymce.is(content) ? content : this.getContent(),
                no_events: !0,
                format: "raw"
            }, ed.onWfEditorSave.dispatch(ed, args), /TEXTAREA|INPUT/i.test(el.nodeName) ? el.value = args.content : el.innerHTML = args.content, 
            debounced && ed.onWfEditorChange.dispatch(ed, args), args.content) : content;
        }, this.toggle = function() {
            var self = this, s = ed.settings, element = ed.getElement(), container = element.parentNode, header = DOM.getPrev(element, ".wf-editor-header"), div = DOM.get(ed.id + "_editor_source"), textarea = DOM.get(ed.id + "_editor_source_textarea"), ifrHeight = parseInt(DOM.get(ed.id + "_ifr").style.height, 10) || s.height, o = tinymce.util.Storage.getHash("TinyMCE_" + ed.id + "_size");
            o && o.height && (ifrHeight = o.height);
            var statusbar, resize, keyup, callback, time, timer, o = (tinymce.is(element.value) ? element.value : element.innerHTML).replace(/<br data-mce-bogus="1"([^>]+)>/gi, ""), element = (div ? (DOM.show(div), 
            textarea.value = o) : (div = DOM.add(container, "div", {
                role: "textbox",
                id: ed.id + "_editor_source",
                class: "wf-editor-source"
            }), element = s.skin_class || "defaultSkin", DOM.addClass(div, element), 
            textarea = DOM.create("textarea", {
                id: ed.id + "_editor_source_textarea"
            }), DOM.add(div, textarea), textarea.value = o, statusbar = DOM.add(div, "div", {
                id: ed.id + "_editor_source_statusbar",
                class: "mceStatusbar mceLast"
            }, '<div class="mcePathRow"></div><div tabindex="-1" class="mceResize" id="' + ed.id + '_editor_source_resize"><span class="mceIcon mce_resize"></span></div>'), 
            resize = DOM.get(ed.id + "_editor_source_resize"), Event.add(resize, "click", function(e) {
                e.preventDefault();
            }), Event.add(resize, "mousedown", function(e) {
                var mm1, mu1, sx, sy, sw, sh, w, h;
                function resizeTo(w, h) {
                    w = Math.max(w, 300), h = Math.max(h, 200), textarea.style.height = h + "px", 
                    container.style.maxWidth = w + "px", ed.settings.container_width = w, 
                    ed.settings.container_height = h + statusbar.offsetHeight, h -= ed.settings.interface_height || 0, 
                    ed.theme.resizeTo(w, h);
                }
                function endResize(e) {
                    e.preventDefault(), Event.remove(DOM.doc, "mousemove", mm1), 
                    Event.remove(DOM.doc, "mouseup", mu1), w = sw + (e.screenX - sx), 
                    h = sh + (e.screenY - sy), resizeTo(w, h), DOM.removeClass(resize, "wf-editor-source-resizing");
                }
                if (e.preventDefault(), DOM.hasClass(resize, "wf-editor-source-resizing")) return endResize(e), 
                !1;
                sx = e.screenX, sy = e.screenY, sw = w = container.offsetWidth, 
                sh = h = textarea.clientHeight, mm1 = Event.add(DOM.doc, "mousemove", function(e) {
                    e.preventDefault(), w = sw + (e.screenX - sx), h = sh + (e.screenY - sy), 
                    resizeTo(w, h), DOM.addClass(resize, "wf-editor-source-resizing");
                }), mu1 = Event.add(DOM.doc, "mouseup", endResize);
            }), function(ed) {
                return window.widgetkit && -1 !== ed.id.indexOf("wk_") || -1 !== ed.id.indexOf("sppb-editor-");
            }(ed) && (callback = function(e) {
                var value = (value = textarea.value).replace(/^\s*|\s*$/g, "");
                self.save(value, !0);
            }, time = 300, (s = function() {
                var args = arguments;
                clearTimeout(timer), timer = setTimeout(function() {
                    callback.apply(this, args);
                }, time);
            }).stop = function() {
                clearTimeout(timer);
            }, keyup = s, DOM.bind(textarea, "input blur", function() {
                keyup();
            }))), DOM.removeClass(container, "mce-loading"), ed.settings.container_height || sessionStorage.getItem("wf-editor-container-height") || ifrHeight + statusbar.offsetHeight);
            DOM.hasClass(container, "mce-fullscreen") && (element = DOM.getViewPort().h - header.offsetHeight - statusbar.offsetHeight - 4), 
            DOM.setStyles(textarea, {
                height: element
            });
        }, this.getSelection = function() {
            return document.getSelection().toString();
        }, this.getCursorPos = function() {
            return 0;
        };
    });
}();