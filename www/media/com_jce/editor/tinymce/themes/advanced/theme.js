/* This file includes original and modified code from various versions of TinyMCE, relicensed under GPL v2+ per LGPL 2.1 §3 where applicable. Source: https://github.com/widgetfactory/tinymce-muon; Copyright (c) Tiny Technologies, Inc. All rights reserved.; Copyright (c) 1999–2015 Ephox Corp. All rights reserved.; Copyright (c) 2009       Moxiecode Systems AB. All rights reserved.; Copyright (c) 2009–2025  Ryan Demmer. All rights reserved.; For a detailed history of modifications, refer to the Git commit history.; Licensed under the GNU General Public License version 2 or later (GPL v2+): https://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, Event = tinymce.dom.Event, extend = tinymce.extend, each = tinymce.each, Storage = tinymce.util.Storage, Delay = tinymce.util.Delay, explode = tinymce.explode;
    tinymce.create("tinymce.themes.AdvancedTheme", {
        controls: {
            bold: [ "bold_desc", "Bold" ],
            italic: [ "italic_desc", "Italic" ],
            underline: [ "underline_desc", "Underline" ],
            strikethrough: [ "striketrough_desc", "Strikethrough" ],
            justifyleft: [ "justifyleft_desc", "JustifyLeft" ],
            justifycenter: [ "justifycenter_desc", "JustifyCenter" ],
            justifyright: [ "justifyright_desc", "JustifyRight" ],
            justifyfull: [ "justifyfull_desc", "JustifyFull" ],
            outdent: [ "outdent_desc", "Outdent" ],
            indent: [ "indent_desc", "Indent" ],
            undo: [ "undo_desc", "Undo" ],
            redo: [ "redo_desc", "Redo" ],
            unlink: [ "unlink_desc", "unlink" ],
            cleanup: [ "cleanup_desc", "mceCleanup" ],
            code: [ "code_desc", "mceCodeEditor" ],
            removeformat: [ "removeformat_desc", "RemoveFormat" ],
            sub: [ "sub_desc", "subscript" ],
            sup: [ "sup_desc", "superscript" ],
            forecolor: [ "forecolor_desc", "ForeColor" ],
            forecolorpicker: [ "forecolor_desc", "mceForeColor" ],
            backcolor: [ "backcolor_desc", "HiliteColor" ],
            backcolorpicker: [ "backcolor_desc", "mceBackColor" ],
            visualaid: [ "visualaid_desc", "mceToggleVisualAid" ],
            newdocument: [ "newdocument_desc", "mceNewDocument" ],
            blockquote: [ "blockquote_desc", "mceBlockQuote" ]
        },
        stateControls: [ "bold", "italic", "underline", "strikethrough", "justifyleft", "justifycenter", "justifyright", "justifyfull", "sub", "sup", "blockquote" ],
        init: function(ed, url) {
            var s, v, self = this;
            self.editor = ed, self.url = url, self.onResolveName = new tinymce.util.Dispatcher(this), 
            self.onResize = new tinymce.util.Dispatcher(this), s = ed.settings, 
            self.settings = s = extend({
                theme_path: !0,
                theme_toolbar_location: "top",
                theme_blockformats: "p,address,pre,h1,h2,h3,h4,h5,h6",
                theme_toolbar_align: "left",
                theme_statusbar_location: "bottom",
                theme_fonts: "Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats",
                theme_more_colors: 1,
                theme_row_height: 23,
                theme_resize_horizontal: 1,
                theme_font_sizes: "1,2,3,4,5,6,7",
                theme_font_selector: "span",
                theme_show_current_color: 0,
                readonly: ed.settings.readonly
            }, s), (v = s.theme_path_location) && "none" != v && (s.theme_statusbar_location = s.theme_path_location), 
            "none" == s.theme_statusbar_location && (s.theme_statusbar_location = 0), 
            ed.onInit.add(function() {
                ed.settings.readonly || (ed.onNodeChange.add(self._nodeChanged, self), 
                ed.onKeyUp.add(self._updateUndoStatus, self), ed.onMouseUp.add(self._updateUndoStatus, self), 
                ed.dom.bind(ed.dom.getRoot(), "dragend", function() {
                    self._updateUndoStatus(ed);
                }), ed.addShortcut("alt+F10,F10", "", function() {
                    self.toolbarGroup.focus();
                }), ed.addShortcut("alt+F11", "", function() {
                    DOM.get(ed.id + "_path_row").focus();
                }));
            }), ed.onPostRender.add(function() {
                var el = ed.getElement(), e = (DOM.setStyle(ed.id + "_tbl", "width", ""), 
                DOM.get(ed.id + "_parent")), ifr = DOM.get(ed.id + "_ifr"), width = s.width || el.style.width;
                (el = s.height || el.style.height) && DOM.setStyle(ifr, "height", el), 
                width && (DOM.setStyle(e.parentNode, "max-width", width), /%/.test(width) || DOM.setStyle(ifr, "max-width", width));
            }), ed.onSetProgressState.add(function(ed, b, ti) {
                var co, tb, id = ed.id;
                b ? self.progressTimer = setTimeout(function() {
                    co = (co = ed.getContainer()).insertBefore(DOM.create("DIV", {
                        style: "position:relative"
                    }), co.firstChild), tb = DOM.get(ed.id + "_tbl"), DOM.add(co, "div", {
                        id: id + "_blocker",
                        class: "mceBlocker",
                        style: {
                            width: tb.clientWidth + 2,
                            height: tb.clientHeight + 2
                        }
                    }), DOM.add(co, "div", {
                        id: id + "_progress",
                        class: "mceProgress",
                        style: {
                            left: tb.clientWidth / 2,
                            top: tb.clientHeight / 2
                        }
                    });
                }, ti || 0) : (DOM.remove(id + "_blocker"), DOM.remove(id + "_progress"), 
                clearTimeout(self.progressTimer));
            }), !1 !== ed.settings.content_css && ed.contentCSS.push(ed.baseURI.toAbsolute(url + "/skins/" + ed.settings.skin + "/content.css"));
        },
        createControl: function(n, cf) {
            var c;
            return cf.createControl(n) || ((c = this.controls[n]) ? cf.createButton(n, {
                title: "advanced." + c[0],
                cmd: c[1],
                ui: c[2],
                value: c[3]
            }) : void 0);
        },
        execCommand: function(cmd, ui, val) {
            return !!(cmd = this["_" + cmd]) && (cmd.call(this, ui, val), !0);
        },
        renderUI: function(o) {
            var n, ic, sc, ed = this.editor, s = this.settings, skin = (ed.settings && (ed.settings.aria_label = s.aria_label + ed.getLang("advanced.help_shortcut")), 
            "mobile" === ed.settings.skin && (ed.settings.skin = "default", s.skin_variant = "touch"), 
            "mce" + this._ufirst(ed.settings.skin) + "Skin");
            return s.skin_variant && (skin += " " + skin + this._ufirst(s.skin_variant)), 
            "default" !== ed.settings.skin && (skin = "mceDefaultSkin " + skin), 
            "rtl" == ed.settings.skin_directionality && (skin += " mceRtl"), ed.settings.skin_class = skin, 
            skin = DOM.create("div", {
                role: "application",
                "aria-label": s.aria_label,
                id: ed.id + "_parent",
                class: "mceEditor " + skin
            }), sc = DOM.add(skin, "div", {
                role: "presentation",
                id: ed.id + "_tbl",
                class: "mceLayout"
            }), ic = this._createLayout(s, sc, o, skin), n = o.targetNode, DOM.insertAfter(skin, n), 
            Event.add(ed.id + "_path_row", "click", function(e) {
                if ((e = DOM.getParent(e.target, "a")) && "A" == e.nodeName) return ed.execCommand("mceSelectNodeDepth", !1, e.getAttribute("data-index") || 0), 
                !1;
            }), "external" == s.theme_toolbar_location && (o.deltaHeight = 0), this.deltaHeight = o.deltaHeight, 
            o.targetNode = null, {
                iframeContainer: ic,
                editorContainer: ed.id + "_parent",
                sizeContainer: sc,
                deltaHeight: o.deltaHeight
            };
        },
        resizeBy: function(dw, dh) {
            var e = DOM.get(this.editor.id + "_ifr");
            this.resizeTo(e.clientWidth + dw, e.clientHeight + dh);
        },
        resizeTo: function(w, h, store) {
            var ed = this.editor, s = this.settings, e = DOM.get(ed.id + "_parent"), ifr = DOM.get(ed.id + "_ifr");
            w = Math.max(s.theme_resizing_min_width || 100, w), h = Math.max(s.theme_resizing_min_height || 100, h), 
            w = Math.min(s.theme_resizing_max_width || 65535, w), h = Math.min(s.theme_resizing_max_height || 65535, h), 
            DOM.setStyle(ifr, "height", h), s.theme_resize_horizontal && (DOM.setStyle(e.parentNode, "max-width", w + "px"), 
            DOM.setStyle(ifr, "max-width", w + "px")), store && !1 !== s.use_state_cookies && Storage.setHash("wf_editor_size_" + ed.id, {
                cw: w,
                ch: h
            }), this.onResize.dispatch();
        },
        destroy: function() {
            var id = this.editor.id;
            Event.clear(id + "_resize"), Event.clear(id + "_path_row"), Event.clear(id + "_external_close");
        },
        _createLayout: function(s, tb, o, p) {
            var lo = s.theme_toolbar_location, sl = s.theme_statusbar_location;
            return s.readonly ? DOM.add(tb, "div", {
                class: "mceIframeContainer"
            }) : ("top" == lo && this._addToolbars(tb, o), "top" == sl && this._addStatusBar(tb, o), 
            s = DOM.add(tb, "div", {
                class: "mceIframeContainer"
            }), "bottom" == lo && this._addToolbars(tb, o), "bottom" == sl && this._addStatusBar(tb, o), 
            s);
        },
        _addControls: function(v, tb) {
            var di, self = this, s = self.settings, cf = self.editor.controlManager;
            s.theme_disable && !self._disabled ? (di = {}, each(explode(s.theme_disable), function(v) {
                di[v] = 1;
            }), self._disabled = di) : di = self._disabled, each(explode(v), function(n) {
                di && di[n] || (n = self.createControl(n, cf)) && tb.add(n);
            });
        },
        _addToolbars: function(c, o) {
            var i, tb, v, a, ed = this.editor, s = this.settings, cf = ed.controlManager, h = [], toolbarsExist = !1, toolbarGroup = cf.createToolbarGroup("toolbargroup", {
                name: ed.getLang("advanced.toolbar"),
                tab_focus_toolbar: ed.getParam("theme_tab_focus_toolbar"),
                class: "ToolbarGroup"
            });
            for (this.toolbarGroup = toolbarGroup, a = s.theme_toolbar_align.toLowerCase(), 
            a = cf.classPrefix + this._ufirst(a), c = DOM.add(c, "div", {
                class: cf.classPrefix + "Toolbar " + a,
                role: "toolbar",
                id: ed.id + "_toolbar"
            }), i = 1; v = s["theme_buttons" + i]; i++) toolbarsExist = !0, tb = cf.createToolbar("toolbar" + i, {
                class: "mceToolbarRow" + i,
                "aria-label": "Toolbar Row " + i
            }), s["theme_buttons" + i + "_add"] && (v += "," + s["theme_buttons" + i + "_add"]), 
            s["theme_buttons" + i + "_add_before"] && (v = s["theme_buttons" + i + "_add_before"] + "," + v), 
            this._addControls(v, tb), toolbarGroup.add(tb), o.deltaHeight -= s.theme_row_height;
            toolbarsExist || (o.deltaHeight -= s.theme_row_height), h.push(toolbarGroup.renderHTML()), 
            DOM.setHTML(c, h.join(""));
        },
        _addStatusBar: function(tb, o) {
            var self = this, ed = self.editor, s = self.settings, n = tb = DOM.add(tb, "div", {
                class: "mceStatusbar"
            });
            s.theme_path && (n = DOM.add(n, "div", {
                id: ed.id + "_path_row",
                role: "group",
                "aria-labelledby": ed.id + "_path_voice",
                class: "mcePathRow"
            }), DOM.add(n, "span", {
                id: ed.id + "_path_voice",
                class: "mcePathLabel"
            }, ed.translate("advanced.path") + ": ")), s.theme_resizing && (DOM.add(tb, "div", {
                id: ed.id + "_resize",
                class: "mceResize",
                tabIndex: "-1"
            }, '<span class="mceIcon mce_resize"></span>'), !1 !== s.use_state_cookies && ed.onPostRender.add(function() {
                var o = Storage.getHash("wf_editor_size_" + ed.id);
                o && self.resizeTo(o.cw, o.ch, !1);
            }), ed.onPostRender.add(function() {
                var elm, parent;
                Event.add(ed.id + "_resize", "click", function(e) {
                    e.preventDefault();
                }), Event.add(ed.id + "_resize", "mousedown", function(e) {
                    var mouseMoveHandler1, mouseMoveHandler2, mouseUpHandler1, mouseUpHandler2, startX, startY, startWidth, startHeight, width, height;
                    function resizeOnMove(e) {
                        e.preventDefault(), width = startWidth + (e.screenX - startX), 
                        height = startHeight + (e.screenY - startY), self.resizeTo(width, height);
                    }
                    function endResize(e) {
                        Event.remove(DOM.doc, "mousemove", mouseMoveHandler1), Event.remove(ed.getDoc(), "mousemove", mouseMoveHandler2), 
                        Event.remove(DOM.doc, "mouseup", mouseUpHandler1), Event.remove(ed.getDoc(), "mouseup", mouseUpHandler2), 
                        width = startWidth + (e.screenX - startX), height = startHeight + (e.screenY - startY), 
                        self.resizeTo(width, height, !0), ed.nodeChanged();
                    }
                    e.preventDefault(), startX = e.screenX, startY = e.screenY, 
                    e = DOM.get(self.editor.id + "_ifr"), startWidth = width = e.clientWidth, 
                    startHeight = height = e.clientHeight, mouseMoveHandler1 = Event.add(DOM.doc, "mousemove", resizeOnMove), 
                    mouseMoveHandler2 = Event.add(ed.getDoc(), "mousemove", resizeOnMove), 
                    mouseUpHandler1 = Event.add(DOM.doc, "mouseup", endResize), 
                    mouseUpHandler2 = Event.add(ed.getDoc(), "mouseup", endResize);
                }), ed.settings.floating_toolbar && (elm = ed.getContainer(), parent = elm.parentNode, 
                Event.add(window, "scroll", Delay.debounce(function() {
                    ed.settings.fullscreen_enabled || (window.pageYOffset > parent.offsetTop ? DOM.addClass(elm, "mceToolbarFixed") : DOM.removeClass(elm, "mceToolbarFixed"));
                }, 128)));
            })), o.deltaHeight -= 21;
        },
        _updateUndoStatus: function(ed) {
            var cm = ed.controlManager, ed = ed.undoManager;
            cm.setDisabled("undo", !ed.hasUndo() && !ed.typing), cm.setDisabled("redo", !ed.hasRedo());
        },
        _nodeChanged: function(ed, cm, n, co, ob) {
            var p, self = this, s = self.settings;
            if (tinymce.each(self.stateControls, function(c) {
                cm.setActive(c, ed.queryCommandState(self.controls[c][1]));
            }), cm.setActive("visualaid", ed.hasVisual), self._updateUndoStatus(ed), 
            cm.setDisabled("outdent", !ed.queryCommandState("Outdent")), s.theme_path && s.theme_statusbar_location) {
                p = DOM.get(ed.id + "_path") || DOM.add(ed.id + "_path_row", "span", {
                    id: ed.id + "_path",
                    class: "mcePathPath"
                }), self.statusKeyboardNavigation && (self.statusKeyboardNavigation.destroy(), 
                self.statusKeyboardNavigation = null), DOM.setHTML(p, "");
                for (var parents = function() {
                    for (var outParents = [], parents = ob.parents, i = parents.length, i = 0, l = parents.length; i < l; i++) 1 != (parent = parents[i]).nodeType || function(node) {
                        return node.hasAttribute("data-mce-bogus") || "bookmark" == node.getAttribute("data-mce-type") || "caret" == node.getAttribute("data-mce-type") || "_mce_caret" == node.id;
                    }(parent) || "BR" == parent.nodeName || parent.hasAttribute("data-mce-bogus") || parent.hasAttribute("data-mce-root") || parent.hasAttribute("data-mce-internal") || outParents.push(parent);
                    return outParents;
                }(), html = "", i = 0, l = parents.length; i < l; i++) {
                    var parent = parents[i], name = {
                        name: name = parent.nodeName.toLowerCase(),
                        node: parent,
                        title: name
                    };
                    self.onResolveName.dispatch(self, name), name.name && (html = name.disabled ? '<span class="mceText">' + name.name + "</span>" + html : '<a role="button" title="' + name.title + '" data-index="' + i + '"><span class="mceText">' + name.name + "</span></a>" + html);
                }
                DOM.setHTML(p, html), 0 < DOM.select("a", p).length && (self.statusKeyboardNavigation = new tinymce.ui.KeyboardNavigation({
                    root: ed.id + "_path_row",
                    items: DOM.select("a", p),
                    excludeFromTabOrder: !0,
                    onCancel: function() {
                        ed.focus();
                    }
                }, DOM));
                var status = DOM.get(ed.id + "_path_row").parentNode, mod = 20;
                tinymce.each(status.childNodes, function(n) {
                    if (DOM.hasClass(n, "mcePathRow")) return !0;
                    mod += n.offsetWidth;
                }), tinymce.each(DOM.select("a", p), function(n) {
                    DOM.removeClass(n, "mcePathHidden"), p.offsetWidth + mod + DOM.getPrev(p, ".mcePathLabel").offsetWidth > status.offsetWidth && DOM.addClass(n, "mcePathHidden");
                });
            }
        },
        _mceNewDocument: function() {
            var ed = this.editor;
            ed.windowManager.confirm({
                title: "advanced.newdocument_desc",
                text: "advanced.newdocument"
            }, function(s) {
                s && ed.execCommand("mceSetContent", !1, "");
            });
        },
        _ufirst: function(s) {
            return s.substring(0, 1).toUpperCase() + s.substring(1);
        }
    }), tinymce.ThemeManager.add("advanced", tinymce.themes.AdvancedTheme);
}();