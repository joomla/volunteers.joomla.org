/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, DOM = tinymce.DOM, Event = tinymce.dom.Event;
    tinymce.PluginManager.add("advlist", function(editor, url) {
        function buildFormats(str) {
            var formats = [];
            return each(str.split(/,/), function(type) {
                var title = type.replace(/-/g, "_");
                formats.push({
                    title: "advlist." + (title = "default" === type ? "def" : title),
                    styles: {
                        listStyleType: "default" === type ? "" : type
                    }
                });
            }), formats;
        }
        var numlist = editor.getParam("advlist_number_styles", "default,lower-alpha,lower-greek,lower-roman,upper-alpha,upper-roman"), numlist = (numlist && (this.numlist = buildFormats(numlist)), 
        editor.getParam("advlist_bullist_styles", "default,circle,disc,square"));
        numlist && (this.bullist = buildFormats(numlist)), this.createControl = function(name, cm) {
            var btn, format, self = this;
            function hasFormat(node, format) {
                var state = !0;
                return each(format.styles, function(value, name) {
                    if (editor.dom.getStyle(node, name) != value) return state = !1;
                }), state;
            }
            function applyListFormat() {
                var list, dom = editor.dom, sel = editor.selection;
                (list = dom.getParent(sel.getNode(), "ol,ul")) && list.nodeName != ("bullist" == name ? "OL" : "UL") && format && !hasFormat(list, format) || editor.execCommand("bullist" == name ? "InsertUnorderedList" : "InsertOrderedList"), 
                format && (list = dom.getParent(sel.getNode(), "ol,ul")) && (dom.setStyles(list, format.styles), 
                list.removeAttribute("data-mce-style"), sel = editor.getParam("advlist_" + name + "_classes", "")) && (sel = sel.trim()).split(/\s+/).forEach(function(cls) {
                    cls && editor.dom.addClass(list, cls);
                }), editor.focus();
            }
            if ("numlist" == name || "bullist" == name) return self[name] && "advlist.def" === self[name][0].title && (format = self[name][0]), 
            self[name] ? ((btn = cm.createSplitButton(name, {
                title: "advanced." + name + "_desc",
                class: "mce_" + name,
                onclick: function(e) {
                    var start_ctrl, reversed_ctrl, form, type_ctrl, styles_ctrl;
                    if (editor.dom.getParent(editor.selection.getNode(), "bullist" == name ? "ul" : "ol") && !e.altKey) return (form = cm.createForm(name + "_form")).empty(), 
                    type_ctrl = cm.createListBox(name + "_type_ctrl", {
                        label: editor.getLang("advlist.type", "Type"),
                        name: "type",
                        onselect: function() {}
                    }), form.add(type_ctrl), each(self[name], function(item) {
                        var icon = (style = item.styles.listStyleType).replace(/-/g, "_"), style = style || "default";
                        type_ctrl.add(editor.getLang(item.title), style, {
                            icon: icon ? "list_" + icon : ""
                        });
                    }), "numlist" == name && (start_ctrl = cm.createTextBox("numlist_start_ctrl", {
                        label: editor.getLang("advlist.start", "Start"),
                        name: "start",
                        subtype: "number",
                        attributes: {
                            min: "1"
                        }
                    }), form.add(start_ctrl), reversed_ctrl = cm.createCheckBox("numlist_reversed_ctrl", {
                        label: editor.getLang("advlist.reversed", "Reversed"),
                        name: "reversed"
                    }), form.add(reversed_ctrl)), e = editor.getParam("advlist_" + name + "_custom_classes", "").trim().split(",").filter(function(cls) {
                        return "" !== cls.trim();
                    }), styles_ctrl = cm.createStylesBox(name + "_class", {
                        label: editor.getLang("adlist.class", "Classes"),
                        onselect: function() {},
                        name: "classes",
                        styles: e
                    }), form.add(styles_ctrl), void editor.windowManager.open({
                        title: editor.getLang("advanced." + name + "_desc", "List"),
                        items: [ form ],
                        size: "mce-modal-landscape-small",
                        open: function() {
                            var label = editor.getLang("update", "Update"), node = editor.selection.getNode(), node = editor.dom.getParent(node, "bullist" == name ? "ul" : "ol"), classes = editor.getParam("advlist_" + name + "_classes", "").trim().split(" ").filter(function(cls) {
                                return "" !== cls.trim();
                            });
                            node && ("numlist" == name && (start_ctrl.value(editor.dom.getAttrib(node, "start") || 1), 
                            reversed_ctrl.checked(!!editor.dom.getAttrib(node, "reversed"))), 
                            classes = (classes = editor.dom.getAttrib(node, "class")).replace(/mce-[\w\-]+/g, "").replace(/\s+/g, " ").trim().split(" ").filter(function(cls) {
                                return "" !== cls.trim();
                            }), node = editor.dom.getStyle(node, "list-style-type") || "default", 
                            type_ctrl.value(node)), styles_ctrl.value(classes), 
                            DOM.setHTML(this.id + "_insert", label);
                        },
                        buttons: [ {
                            title: editor.getLang("remove", "Remove"),
                            id: "remove",
                            onclick: function() {
                                applyListFormat(), this.close();
                            }
                        }, {
                            title: editor.getLang("cancel", "Cancel"),
                            id: "cancel"
                        }, {
                            title: editor.getLang("insert", "Insert"),
                            id: "insert",
                            onsubmit: function(e) {
                                var data = form.submit(), list = (Event.cancel(e), 
                                editor.dom.getParent(editor.selection.getNode(), "bullist" == name ? "ul" : "ol"));
                                list && ("default" == data.type ? editor.dom.setStyles(list, {
                                    "list-style-type": ""
                                }) : editor.dom.setStyles(list, {
                                    "list-style-type": data.type
                                }), list.removeAttribute("data-mce-style"), each(data, function(value, key) {
                                    value = value || null, "start" == key && "1" == value && (value = null), 
                                    "type" == key && (value = null), editor.dom.setAttrib(list, key = "classes" == key ? "class" : key, value);
                                }), editor.undoManager.add());
                            },
                            classes: "primary",
                            scope: self
                        } ]
                    });
                    applyListFormat();
                }
            })).onRenderMenu.add(function(btn, menu) {
                menu.onHideMenu.add(function() {
                    self.bookmark && (editor.selection.moveToBookmark(self.bookmark), 
                    self.bookmark = 0);
                }), menu.onShowMenu.add(function() {
                    var fmtList, list = editor.dom.getParent(editor.selection.getNode(), "ol,ul");
                    (list || format) && (fmtList = self[name], each(menu.items, function(item) {
                        var state = !0;
                        item.setSelected(0), list && !item.isDisabled() && (each(fmtList, function(fmt) {
                            if (fmt.id == item.id && !hasFormat(list, fmt)) return state = !1;
                        }), state) && item.setSelected(1);
                    }), list || menu.items[format.id].setSelected(1)), editor.focus(), 
                    tinymce.isIE && (self.bookmark = editor.selection.getBookmark(1));
                }), each(self[name], function(item) {
                    item.id = editor.dom.uniqueId();
                    var icon = item.styles.listStyleType.replace(/-/g, "_");
                    menu.add({
                        id: item.id,
                        title: item.title,
                        icon: "list_" + icon,
                        onclick: function() {
                            format = item, applyListFormat();
                        }
                    });
                });
            }), btn) : cm.createButton(name, {
                title: "advanced." + name + "_desc",
                class: "mce_" + name,
                onclick: function() {
                    applyListFormat();
                }
            });
        };
    });
}();