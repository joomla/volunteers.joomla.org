/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, DOM = tinymce.DOM;
    function addZeros(value, len) {
        var i;
        if ((value = "" + value).length < len) for (i = 0; i < len - value.length; i++) value = "0" + value;
        return value;
    }
    tinymce.PluginManager.add("reference", function(ed, url) {
        function openDialog(tag) {
            var cm = ed.controlManager, form = cm.createForm("reference_form");
            "q" == tag ? form.add(cm.createTextBox("reference_cite", {
                label: ed.getLang("reference.label_cite", "Cite"),
                name: "cite"
            })) : form.add(cm.createTextBox("attributes_title", {
                label: ed.getLang("attributes.label_title", "Title"),
                name: "title"
            })), "ins" != tag && "del" != tag || (form.add(cm.createTextBox("reference_cite", {
                label: ed.getLang("reference.label_cite", "Cite"),
                name: "cite"
            })), form.add(cm.createTextBox("reference_datetime", {
                label: ed.getLang("reference.label_datetime", "Date/Time"),
                name: "datetime",
                button: {
                    icon: "date",
                    label: ed.getLang("reference.label_datetime", "Date/Time"),
                    click: function() {
                        var d;
                        this.value((d = new Date(), ed.getParam("reference_datetime", "%Y-%m-%dT%H:%M:%S").replace("%D", "%m/%d/%y").replace("%r", "%I:%M:%S %p").replace("%Y", "" + d.getFullYear()).replace("%y", "" + d.getYear()).replace("%m", addZeros(d.getMonth() + 1, 2)).replace("%d", addZeros(d.getDate(), 2)).replace("%H", "" + addZeros(d.getHours(), 2)).replace("%M", "" + addZeros(d.getMinutes(), 2)).replace("%S", "" + addZeros(d.getSeconds(), 2)).replace("%I", "" + ((d.getHours() + 11) % 12 + 1)).replace("%p", d.getHours() < 12 ? "AM" : "PM").replace("%%", "%")));
                    }
                }
            }))), ed.windowManager.open({
                title: ed.getLang("reference." + tag + "_title", "Reference"),
                items: [ form ],
                size: "mce-modal-landscape-small",
                open: function() {
                    var update, node = ed.selection.getNode(), attribs = {};
                    each([ "title", "datetime", "cite" ], function(name) {
                        if (!node.hasAttribute(name)) return !0;
                        attribs[name] = ed.dom.getAttrib(node, name), update = !0;
                    }), update && DOM.setHTML(this.id + "_insert", ed.getLang("update", "Update")), 
                    form.update(attribs);
                },
                buttons: [ {
                    title: ed.getLang("common.cancel", "Cancel"),
                    id: "cancel"
                }, {
                    title: ed.getLang("common.remove", "Remove"),
                    onsubmit: function() {
                        ed.selection.getNode().nodeName.toLowerCase() == tag && (ed.formatter.remove(tag), 
                        ed.undoManager.add());
                    }
                }, {
                    title: ed.getLang("common.insert", "Insert"),
                    id: "insert",
                    onsubmit: function(e) {
                        tinymce.dom.Event.cancel(e);
                        var e = ed.selection.getNode(), data = form.submit(), e = ed.dom.getParent(e, tag);
                        ed.formatter.apply(tag, data, e), ed.undoManager.add();
                    },
                    classes: "primary",
                    autofocus: !0
                } ]
            });
        }
        ed.addButton("cite", {
            title: "reference.cite_title",
            onclick: function() {
                openDialog("cite");
            }
        }), ed.addButton("q", {
            title: "reference.q_title",
            onclick: function() {
                openDialog("q");
            }
        }), "html5-strict" !== ed.settings.schema && ed.addButton("acronym", {
            title: "reference.acronym_title",
            onclick: function() {
                openDialog("acronym");
            }
        }), ed.addButton("abbr", {
            title: "reference.abbr_title",
            onclick: function() {
                openDialog("abbr");
            }
        }), ed.addButton("del", {
            title: "reference.del_title",
            onclick: function() {
                openDialog("del");
            }
        }), ed.addButton("ins", {
            title: "reference.ins_title",
            onclick: function() {
                openDialog("ins");
            }
        }), ed.onNodeChange.add(function(ed, cm, n, co) {
            var p = ed.dom.getParent(n, "CITE,ACRONYM,ABBR,DEL,INS,Q");
            if (cm.setDisabled("cite", co), cm.setDisabled("acronym", co), cm.setDisabled("abbr", co), 
            cm.setDisabled("del", co), cm.setDisabled("ins", co), cm.setDisabled("q", co), 
            cm.setActive("cite", 0), cm.setActive("acronym", 0), cm.setActive("abbr", 0), 
            cm.setActive("del", 0), cm.setActive("ins", 0), cm.setActive("q", 0), 
            p) for (;cm.setDisabled(p.nodeName.toLowerCase(), 0), cm.setActive(p.nodeName.toLowerCase(), 1), 
            p = p.parentNode; );
        }), ed.onPreInit.add(function() {
            ed.dom.create("abbr");
            var formats = {};
            each([ "cite", "acronym", "abbr", "del", "ins", "q" ], function(name) {
                formats[name] = {
                    inline: name,
                    remove: "all",
                    onformat: function(elm, fmt, vars) {
                        each(vars, function(value, key) {
                            ed.dom.setAttrib(elm, key, value);
                        });
                    }
                };
            }), ed.formatter.register(formats);
        });
    });
}();