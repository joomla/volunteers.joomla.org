/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, Event = tinymce.dom.Event, extend = tinymce.extend;
    function isMediaObject(node) {
        return node.getAttribute("data-mce-object") || node.getAttribute("data-mce-type");
    }
    function isImage(node) {
        return node && "IMG" === node.nodeName && !isMediaObject(node);
    }
    function getUploadConfig(ed) {
        return ed.getParam("imgmanager", {}).upload || {};
    }
    function getUploadURL(ed, file) {
        var data = getUploadConfig(ed);
        return !!(data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(file.name)) && ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=image";
    }
    tinymce.PluginManager.add("imgmanager", function(ed, url) {
        var self = this;
        function openDialog() {
            var node = ed.selection.getNode();
            "IMG" == node.nodeName && isMediaObject(node) || ed.windowManager.open({
                file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=image",
                size: "mce-modal-portrait-full"
            }, {
                plugin_url: url
            });
        }
        function insertImage(args) {
            var node = ed.selection.getNode();
            isImage(node) ? ed.dom.setAttribs(node, {
                src: args.src,
                alt: args.alt || "",
                class: args.class || ""
            }) : (ed.execCommand("mceInsertContent", !1, '<img id="__mce_tmp" src="" />', {
                skip_undo: 1
            }), node = ed.dom.get("__mce_tmp"), ed.dom.setAttribs(node, args), ed.dom.setAttrib(node, "id", "")), 
            ed.selection.select(node), ed.undoManager.add(), ed.nodeChanged();
        }
        function getDataAndInsert(args) {
            var params = ed.getParam("imgmanager", {});
            return new Promise(function(resolve, reject) {
                var value;
                !1 !== params.always_include_dimensions ? (ed.setProgressState(!0), 
                value = args.src, new Promise(function(resolve, reject) {
                    if (!value) return resolve();
                    var img = new Image();
                    img.onload = function() {
                        resolve({
                            width: img.width,
                            height: img.height
                        });
                    }, img.onerror = function() {
                        reject();
                    }, img.src = ed.documentBaseURI.toAbsolute(value);
                }).then(function(data) {
                    ed.setProgressState(!1), insertImage(extend(args, data)), resolve();
                }, function() {
                    ed.setProgressState(!1), reject();
                })) : (insertImage(args), resolve());
            });
        }
        ed.addCommand("mceImageManager", function() {
            openDialog();
        }), ed.addCommand("mceImage", function() {
            openDialog();
        }), ed.addButton("imgmanager", {
            title: "imgmanager.desc",
            cmd: "mceImage"
        }), ed.onNodeChange.add(function(ed, cm, n, collapsed) {
            n = isImage(n);
            cm.setDisabled("imgmanager", !n && !collapsed), cm.setActive("imgmanager", n);
        }), ed.onPreInit.add(function() {
            var cm, form, urlCtrl, descriptionCtrl, stylesListCtrl, args, uploader, params = ed.getParam("imgmanager", {}), isMobile = window.matchMedia("(max-width: 600px)").matches;
            !0 === (!0 === params.basic_dialog || isMobile) && (cm = ed.controlManager, 
            form = cm.createForm("image_form"), !(args = {
                label: ed.getLang("dlg.url", "URL"),
                name: "url",
                clear: !0
            }) !== params.basic_dialog_filebrowser && (params.basic_dialog_filebrowser || isMobile) && extend(args, {
                picker: !0,
                picker_label: "browse",
                picker_icon: "image",
                onpick: function() {
                    ed.execCommand("mceFileBrowser", !0, {
                        caller: "imgmanager",
                        callback: function(selected, data) {
                            var src;
                            data.length && (src = data[0].url, data = data[0].title, 
                            urlCtrl.value(src), data = data.replace(/\.[^.]+$/i, ""), 
                            descriptionCtrl.value(data), window.setTimeout(function() {
                                urlCtrl.focus();
                            }, 10));
                        },
                        filter: params.filetypes || "images",
                        value: urlCtrl.value()
                    });
                }
            }), uploader = ed.plugins.upload || !1, params.upload && uploader && extend(args, {
                upload_label: "upload.label",
                upload_accept: params.upload.filetypes,
                upload: function(e, file) {
                    if (file && file.name) {
                        var url = getUploadURL(ed, file);
                        if (!url) return ed.windowManager.alert({
                            text: ed.getLang("upload.file_extension_error", "File type not supported"),
                            title: ed.getLang("upload.error", "Upload Error")
                        }), !1;
                        urlCtrl.setLoading(!0), extend(file, {
                            filename: file.name.replace(/[\+\\\/\?\#%&<>"\'=\[\]\{\},;@\^\(\)\xa3\u20ac$~]/g, ""),
                            upload_url: url
                        }), uploader.upload(file, function(response) {
                            urlCtrl.setLoading(!1);
                            var response = response.files || [], response = response.length ? response[0] : {};
                            if (response.file) return urlCtrl.value(response.file), 
                            response = (response = response.alt || response.name || "").replace(/\.[^.]+$/i, ""), 
                            descriptionCtrl.value(response), !0;
                            ed.windowManager.alert({
                                text: "File upload failed!",
                                title: ed.getLang("upload.error", "Upload Error")
                            });
                        }, function(message) {
                            ed.windowManager.alert({
                                text: message,
                                title: ed.getLang("upload.error", "Upload Error")
                            }), urlCtrl.setLoading(!1);
                        });
                    }
                }
            }), urlCtrl = cm.createUrlBox("image_url", args), form.add(urlCtrl), 
            descriptionCtrl = cm.createTextBox("image_description", {
                label: ed.getLang("dlg.description", "Description"),
                name: "alt",
                clear: !0
            }), form.add(descriptionCtrl), !1 !== params.basic_dialog_classes && (stylesListCtrl = cm.createStylesBox("image_class", {
                label: ed.getLang("image.class", "Classes"),
                onselect: function() {},
                name: "classes",
                styles: params.custom_classes || []
            }), form.add(stylesListCtrl)), ed.addCommand("mceImage", function() {
                var node = ed.selection.getNode();
                "IMG" == node.nodeName && isMediaObject(node) || ed.windowManager.open({
                    title: ed.getLang("imgmanager.desc", "Image"),
                    items: [ form ],
                    size: "mce-modal-landscape-small",
                    open: function() {
                        var label = ed.getLang("insert", "Insert"), node = ed.selection.getNode(), src = "", alt = "", classes = params.attributes.classes || "";
                        isImage(node) && ((src = ed.dom.getAttrib(node, "src")) && (label = ed.getLang("update", "Update")), 
                        alt = ed.dom.getAttrib(node, "alt"), classes = ed.dom.getAttrib(node, "class")), 
                        urlCtrl.value(src), descriptionCtrl.value(alt), stylesListCtrl && (classes = classes.replace(/mce-[\w\-]+/g, "").replace(/\s+/g, " ").trim().split(" ").filter(function(cls) {
                            return "" !== cls.trim();
                        }), stylesListCtrl.value(classes)), window.setTimeout(function() {
                            urlCtrl.focus();
                        }, 10), DOM.setHTML(this.id + "_insert", label);
                    },
                    buttons: [ {
                        title: ed.getLang("cancel", "Cancel"),
                        id: "cancel"
                    }, {
                        title: ed.getLang("insert", "Insert"),
                        id: "insert",
                        onsubmit: function(e) {
                            var data = form.submit(), node = ed.selection.getNode();
                            if (Event.cancel(e), !data.url) return isImage(node) && ed.dom.remove(node), 
                            !1;
                            e = {
                                src: data.url,
                                alt: data.alt
                            };
                            (e = extend(e, self.getAttributes(params))).class = data.classes || e.class || "", 
                            getDataAndInsert(e).then();
                        },
                        classes: "primary",
                        scope: self
                    } ]
                });
            }));
        }), ed.onInit.add(function() {
            ed && ed.plugins.contextmenu && ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                m.add({
                    title: "imgmanager.desc",
                    icon: "imgmanager",
                    cmd: "mceImage"
                });
            });
        }), this.getAttributes = function(data) {
            var attr = {
                style: {}
            }, attribs = data.attributes || {};
            return attribs.style && tinymce.is(attribs.style, "string") && (attribs.style = ed.dom.parseStyle(attribs.style)), 
            attribs.styles && tinymce.is(attribs.styles, "object") && (attribs.style = extend(attribs.styles, attribs.style || {}), 
            delete attribs.styles), attribs.style && (attribs.style = ed.dom.serializeStyle(attribs.style)), 
            tinymce.each([ "alt", "title", "id", "dir", "class", "usemap", "style", "longdesc", "loading" ], function(key) {
                tinymce.is(attribs[key]) && (attr[key] = attribs[key]);
            }), data.width && (attr.width = data.width), data.height && (attr.height = data.height), 
            attr;
        }, this.insertUploadedFile = function(o) {
            var data = this.getUploadConfig();
            if (data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(o.name)) return data = {
                src: o.file,
                alt: o.alt || o.name,
                style: {}
            }, data = extend(data, this.getAttributes(o)), ed.dom.create("img", data);
            return !1;
        }, this.getUploadURL = function(file) {
            return !ed.plugins.imgmanager_ext && getUploadURL(ed, file);
        }, this.getUploadConfig = function() {
            return getUploadConfig(ed);
        };
    });
}();