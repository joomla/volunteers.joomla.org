/* jce - 2.9.99.12 | 2026-10-07 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html; see LICENSE.txt */
!function() {
    var each = tinymce.each, JSON = tinymce.util.JSON, RangeUtils = tinymce.dom.RangeUtils, Uuid = tinymce.util.Uuid, Env = tinymce.util.Env;
    tinymce.PluginManager.add("upload", function(ed) {
        var plugins = [], files = [];
        function hasFiles(e) {
            e = e.dataTransfer;
            return e && e.files && e.files.length;
        }
        ed.onPreInit.add(function() {
            function bindUploadEvents(ed) {
                each(ed.dom.select(".mce-item-upload-marker", ed.getBody()), function(n) {
                    0 == plugins.length ? ed.dom.remove(n) : bindUploadMarkerEvents(n);
                });
            }
            each(ed.plugins, function(plg) {
                var data;
                tinymce.is(plg.getUploadConfig, "function") && (data = plg.getUploadConfig()).inline && data.filetypes && plugins.push(plg);
            }), ed.onBeforeSetContent.add(function(ed, o) {
                o.content = o.content.replace(/<\/media>/g, "&nbsp;</media>");
            }), ed.onPostProcess.add(function(ed, o) {
                o.content = o.content.replace(/(&nbsp;|\u00a0)<\/media>/g, "</media>");
            }), ed.schema.addValidElements("+media[type|width|height|class|style|title|*]"), 
            ed.serializer.addAttributeFilter("data-mce-marker", function(nodes) {
                for (var i = nodes.length; i--; ) nodes[i].remove();
            }), ed.parser.addNodeFilter("img,media", function(nodes) {
                for (var node, i = nodes.length; i--; ) !function(node) {
                    if ("media" === node.name) return 1;
                    if ("img" === node.name) {
                        if (node.attr("data-mce-upload-marker")) return 1;
                        node = node.attr("class");
                        if (node && -1 != node.indexOf("upload-placeholder")) return 1;
                    }
                }(node = nodes[i]) || (0 == plugins.length ? node.remove() : function(node) {
                    var src = node.attr("src") || "", style = {}, cls = [];
                    node.attr("alt") || /data:image/.test(src) || node.attr("alt", src.slice(src.lastIndexOf("/") + 1));
                    node.attr("style") && (style = ed.dom.styles.parse(node.attr("style")));
                    node.attr("hspace") && (style["margin-left"] = style["margin-right"] = node.attr("hspace"));
                    node.attr("vspace") && (style["margin-top"] = style["margin-bottom"] = node.attr("vspace"));
                    node.attr("align") && (style.float = node.attr("align"));
                    node.attr("class") && (cls = node.attr("class").replace(/\s*upload-placeholder\s*/, "").split(" "));
                    cls.push("mce-item-upload"), cls.push("mce-item-upload-marker"), 
                    "media" === node.name && (node.name = "img", node.shortEnded = !0);
                    node.attr({
                        src: Env.transparentSrc,
                        class: tinymce.trim(cls.join(" "))
                    });
                    src = ed.dom.create("span", {
                        style: style
                    }), cls = ed.dom.getAttrib(src, "style");
                    cls && node.attr({
                        style: cls,
                        "data-mce-style": cls
                    });
                }(node));
            }), ed.serializer.addNodeFilter("img", function(nodes) {
                for (var node, cls, i = nodes.length; i--; ) (cls = (node = nodes[i]).attr("class")) && /mce-item-upload-marker/.test(cls) && (cls = cls.replace(/(?:^|\s)(mce-item-)(?!)(upload|upload-marker|upload-placeholder)(?!\S)/g, ""), 
                node.attr({
                    "data-mce-src": "",
                    src: "",
                    class: tinymce.trim(cls)
                }), node.name = "media", node.shortEnded = !1, node.attr("alt", null), 
                node.attr("data-mce-upload-marker", null));
            }), ed.onSetContent.add(function() {
                bindUploadEvents(ed);
            }), ed.onFullScreen && ed.onFullScreen.add(function(editor) {
                bindUploadEvents(editor);
            });
        }), ed.onInit.add(function() {
            function cancelEvent(e) {
                e.preventDefault(), e.stopPropagation();
            }
            0 == plugins.length ? ed.dom.bind(ed.getBody(), "dragover drop", function(e) {
                hasFiles(e) && e.preventDefault();
            }) : (ed.theme && ed.theme.onResolveName && ed.theme.onResolveName.add(function(theme, o) {
                var n = o.node;
                n && "IMG" === n.nodeName && /mce-item-upload/.test(n.className) && (o.name = "placeholder");
            }), ed.dom.bind(ed.getBody(), "dragover", function(e) {
                e.dataTransfer.dropEffect = tinymce.VK.metaKeyPressed(e) ? "copy" : "move";
            }), ed.dom.bind(ed.getBody(), "drop", function(e) {
                var rng;
                hasFiles(e) && ((rng = RangeUtils.getCaretRangeFromPoint(e.clientX, e.clientY, ed.getDoc())) && ed.selection.setRng(rng), 
                ed.undoManager.add(), each(e.dataTransfer.files, function(file) {
                    addFile(file);
                }), cancelEvent(e)), each(files.slice(0), function(file) {
                    uploadFile(file);
                }), tinymce.isGecko && "IMG" == e.target.nodeName && cancelEvent(e);
            }));
        });
        var noop = function() {};
        function uploadHandler(file, success, failure, progress) {
            success = success || noop, failure = failure || noop, progress = progress || noop;
            var xhr, formData, args = {
                method: "upload",
                id: Uuid.uuid("wf_"),
                inline: 1,
                name: file.filename
            }, url = file.upload_url;
            url += "&" + ed.settings.query, (xhr = new XMLHttpRequest()).open("POST", url), 
            xhr.upload.onprogress = function(e) {
                progress(e.loaded / e.total * 100);
            }, xhr.onerror = function() {
                failure("Image upload failed due to a XHR Transport error. Code: " + xhr.status);
            }, xhr.onload = function() {
                var json;
                if (xhr.status < 200 || 300 <= xhr.status) failure("HTTP Error: " + xhr.status); else {
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch (e) {
                        json = null;
                    }
                    json && !json.error && json.result ? success(json.result) : failure(json && json.error && json.error.message ? json.error.message : "Invalid JSON response!");
                }
            }, formData = new FormData(), each(args, function(value, name) {
                formData.append(name, value);
            }), formData.append("file", file, file.name), xhr.send(formData);
        }
        function showUploadError(text) {
            ed.windowManager.alert({
                text: text,
                title: ed.getLang("upload.error", "Upload Error")
            });
        }
        function showFileTypeError() {
            showUploadError(ed.getLang("upload.file_extension_error", "File type not supported"));
        }
        function addFile(file) {
            if (!/\.(php([0-9]*)|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)\./i.test(file.name) && function(file) {
                return each(plugins, function(plg) {
                    var url = plg.getUploadURL(file);
                    if (url) return file.upload_url = url, file.uploader = plg, 
                    !1;
                }), file.upload_url;
            }(file)) return function(file) {
                if (!tinymce.is(file.uploader.getUploadConfig, "function")) return 1;
                var config = file.uploader.getUploadConfig();
                if (new RegExp("\\.(" + config.filetypes.join("|") + ")$", "i").test(file.name)) {
                    if (file.size) {
                        config = parseInt(config.max_size, 10) || 1024;
                        if (file.size > 1024 * config) return showUploadError(ed.getLang("upload.file_size_error", "File size exceeds maximum allowed size")), 
                        0;
                    }
                    return 1;
                }
                showFileTypeError();
            }(file) && (file.filename = (file.target_name || file.name).replace(/[\+\\\/\?\#%&<>"\'=\[\]\{\},;@\^\(\)\xa3\u20ac$~]/g, ""), 
            file.marker || !1 === ed.settings.upload_use_placeholder || (file.marker = function(file) {
                var uid = Uuid.uuid("wf-tmp-"), uid = (ed.execCommand("mceInsertContent", !1, '<span data-mce-marker="1" id="' + uid + '">\ufeff</span>', {
                    skip_undo: 1
                }), ed.dom.get(uid));
                return /image\/(gif|png|jpeg|jpg)/.test(file.type) && file.size ? (file = Math.max(300, Math.round(Math.sqrt(file.size))), 
                ed.dom.setStyles(uid, {
                    width: file,
                    height: file
                }), ed.dom.addClass(uid, "mce-item-upload")) : ed.setProgressState(!0), 
                uid;
            }(file)), files.push(file), 1);
            showFileTypeError();
        }
        function selectAndInsert(file, data) {
            var marker = file.marker, file = (ed.selection.select(marker), file.uploader.insertUploadedFile(data));
            file && ("object" == typeof file && file.nodeType && (ed.dom.hasClass(marker, "mce-item-upload-marker") && !function(marker, elm) {
                var styles = ed.dom.getAttrib(marker, "data-mce-style"), w = marker.width || 0, marker = marker.height || 0;
                styles && ((styles = ed.dom.styles.parse(styles)).width && (w = styles.width, 
                delete styles.width), styles.height && (marker = styles.height, 
                delete styles.height), ed.dom.setStyles(elm, styles)), w && ed.dom.setAttrib(elm, "width", w), 
                marker && ed.dom.setAttrib(elm, "height", w ? "" : marker);
            }(marker, file), ed.execCommand("mceInsertContent", !1, ed.dom.getOuterHTML(file))), 
            ed.nodeChanged());
        }
        function bindUploadMarkerEvents(marker) {
            var dom = tinymce.DOM;
            function removeUpload() {
                dom.setStyles("wf_upload_button", {
                    top: "",
                    left: "",
                    display: "none",
                    zIndex: ""
                });
            }
            ed.onNodeChange.add(removeUpload), ed.dom.bind(ed.getWin(), "scroll", removeUpload);
            var btn, input = dom.get("wf_upload_input");
            dom.get("wf_upload_button") || (btn = dom.add(dom.doc.body, "div", {
                id: "wf_upload_button",
                class: "btn",
                role: "button",
                title: ed.getLang("upload.button_description", "Click to upload a file")
            }, '<label for="wf_upload_input"><span class="icon-upload"></span>&nbsp;' + ed.getLang("upload.label", "Upload") + "</label>"), 
            input = dom.add(btn, "input", {
                type: "file",
                id: "wf_upload_input"
            })), ed.dom.bind(marker, "mouseover", function(e) {
                var vp, p1, p2, x, y, zIndex;
                ed.dom.getAttrib(marker, "data-mce-selected") || (vp = ed.dom.getViewPort(ed.getWin()), 
                p1 = dom.getRect(ed.getContentAreaContainer()), p2 = ed.dom.getRect(marker), 
                vp.y > p2.y + p2.h / 2 - 25) || vp.y < p2.y + p2.h / 2 + 25 - p1.h || (x = Math.max(p2.x - vp.x, 0) + p1.x, 
                y = Math.max(p2.y - vp.y, 0) + p1.y - Math.max(vp.y - p2.y, 0), 
                zIndex = "mce_fullscreen" == ed.id ? dom.get("mce_fullscreen_container").style.zIndex : 0, 
                each([ "wf_upload_button", "wf_select_button" ], function(id) {
                    dom.setStyles(id, {
                        top: y + p2.h / 2 - 16,
                        left: x + p2.w / 2 - 50,
                        display: "block",
                        zIndex: zIndex + 1
                    });
                }), input.onchange = function() {
                    var file = input.files ? input.files[0] : null;
                    file && (file.marker = marker, ed.undoManager.add(), addFile(file)) && (each([ "width", "height" ], function(key) {
                        ed.dom.setStyle(marker, key, ed.dom.getAttrib(marker, key));
                    }), file.marker = ed.dom.rename(marker, "span"), uploadFile(file), 
                    removeUpload());
                });
            }), ed.dom.bind(marker, "mouseout", function(e) {
                !e.relatedTarget && 0 < e.clientY || removeUpload();
            });
        }
        function uploadFile(file) {
            function cleanup() {
                !function(file) {
                    -1 !== (file = tinymce.inArray(files, file)) && files.splice(file, 1);
                }(file), file.marker && ed.dom.remove(file.marker), ed.setProgressState(!1);
            }
            uploadHandler(file, function(response) {
                response = response.files || [], response = response.length ? response[0] : {};
                file.uploader && selectAndInsert(file, tinymce.extend({
                    type: file.type,
                    name: file.name
                }, response)), cleanup();
            }, function(message) {
                showUploadError(message), cleanup();
            }, function(value) {
                file.marker && ed.dom.setAttrib(file.marker, "data-progress", value);
            });
        }
        this.plugins = plugins, this.upload = uploadHandler;
    });
}();