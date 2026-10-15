/* jce - 2.9.99.12 | 2026-10-07 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html; see LICENSE.txt */
!function() {
    var each = tinymce.each, BlobCache = tinymce.file.BlobCache, Conversions = tinymce.file.Conversions, Uuid = tinymce.util.Uuid, DOM = tinymce.DOM, invalidCharacters = /[\+\\\/\?\#%&<>"\'=\[\]\{\},;@\^\(\)\xa3\u20ac$~]/g, invalidExtensions = /\.(php([0-9]*)|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)\b/i, count = 0;
    function uploadHandler(settings, blobInfo, success, failure, progress) {
        var xhr = new XMLHttpRequest(), formData = new FormData();
        xhr.open("POST", settings.url), xhr.upload.onprogress = function(e) {
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
                json && !json.error && json.result && json.result.files && json.result.files.length ? success(json.result.files[0]) : failure(function(json) {
                    return json && json.error && json.error.message ? json.error.message : "Invalid JSON response!";
                }(json));
            }
        }, formData.append("file", settings.blob || blobInfo.blob(), settings.name || blobInfo.filename()), 
        each(settings, function(value, name) {
            if ("url" == name || "multipart" == name || "blob" == name) return !0;
            formData.append(name, value);
        }), xhr.send(formData);
    }
    function createBlobInfo(blob, base64) {
        blob = BlobCache.create("blobid" + count++, blob, base64);
        return BlobCache.add(blob), blob;
    }
    function processImages(images) {
        var cache = {}, images = tinymce.map(images, function(img) {
            return cache[img.src] || (cache[img.src] = function(img) {
                return new Promise(function(resolve, reject) {
                    var base64, blobInfo;
                    if (0 === img.src.indexOf("blob:")) return (blobInfo = BlobCache.getByUri(img.src)) ? void resolve({
                        image: img,
                        blobInfo: blobInfo
                    }) : void Conversions.uriToBlob(img.src).then(function(blob) {
                        return Conversions.blobToDataUri(blob).then(function(dataUri) {
                            dataUri = Conversions.parseDataUri(dataUri).data;
                            resolve({
                                image: img,
                                blobInfo: createBlobInfo(blob, dataUri)
                            });
                        });
                    }, reject);
                    base64 = Conversions.parseDataUri(img.src).data, (blobInfo = BlobCache.findFirst(function(cachedBlobInfo) {
                        return cachedBlobInfo.base64() === base64;
                    })) ? resolve({
                        image: img,
                        blobInfo: blobInfo
                    }) : Conversions.uriToBlob(img.src).then(function(blob) {
                        resolve({
                            image: img,
                            blobInfo: createBlobInfo(blob, base64)
                        });
                    }, reject);
                });
            }(img).catch(function() {
                return null;
            })), cache[img.src].then(function(result) {
                return result ? {
                    image: img,
                    blobInfo: result.blobInfo
                } : null;
            });
        });
        return Promise.all(images);
    }
    function isUploadableImage(img) {
        var src = img.getAttribute("src");
        return !(img.hasAttribute("data-mce-bogus") || img.hasAttribute("data-mce-placeholder") || img.hasAttribute("data-mce-upload-marker") || !src || "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" == src || 0 !== src.indexOf("blob:") && 0 !== src.indexOf("data:"));
    }
    tinymce.PluginManager.add("blobupload", function(ed) {
        var uploaders = [], pending = {};
        function removePendingImages(content) {
            return function() {
                for (var src in pending) return 1;
            }() ? content.replace(/<img(?:[^>"']|"[^"]*"|'[^']*')*>/gi, function(image) {
                var match = /\ssrc="([^"]*)"/i.exec(image);
                return match && pending[match[1]] ? "" : image;
            }) : content;
        }
        function getMarkerImages(marker) {
            return tinymce.grep(ed.dom.select("img[src]"), function(image) {
                return image.src == marker.src;
            });
        }
        function findMarker(marker) {
            return getMarkerImages(marker)[0];
        }
        function removeMarker(marker) {
            each(getMarkerImages(marker), function(image) {
                ed.selection.select(image), ed.execCommand("mceRemoveNode");
                image = ed.selection.getNode();
                "P" == image.nodeName && ed.dom.isEmpty(image) && ed.dom.add(image, "br", {
                    "data-mce-bogus": 1
                });
            });
        }
        function showUploadError(message) {
            ed.windowManager.alert({
                text: message,
                title: ed.getLang("upload.error", "Upload Error")
            });
        }
        function uploadPastedImage(marker, blobInfo) {
            return new Promise(function(resolve) {
                if (!uploaders.length) return removeMarker(marker), resolve();
                function cancel() {
                    removeMarker(marker), resolve();
                }
                var html, win = ed.windowManager.open({
                    title: ed.getLang("dlg.name", "Name"),
                    content: (html = '<div class="mceForm"><p>' + ed.getLang("upload.name_description", "Please supply a name for this file") + '</p><div class="mceModalRow">   <label for="' + ed.id + '_blob_input">' + ed.getLang("dlg.name", "Name") + '</label>   <div class="mceModalControl mceModalControlAppend">       <input type="text" id="' + ed.id + '_blob_input" autofocus />       <select id="' + ed.id + '_blob_mimetype">           <option value="jpeg">jpeg</option>           <option value="png">png</option>       </select>   </div></div><div class="mceModalRow">   <label for="' + ed.id + '_blob_quality">' + ed.getLang("dlg.quality", "Quality") + '</label>   <div class="mceModalControl">       <select id="' + ed.id + '_blob_quality" class="mce-flex-25">', 
                    each([ 100, 90, 80, 70, 60, 50, 40, 30, 20, 10 ], function(value) {
                        html += '<option value="' + value + '">' + value + "</option>";
                    }), html += '       </select>       <span role="presentation">%</span>   </div></div></div>'),
                    size: "mce-modal-landscape-small",
                    buttons: [ {
                        title: ed.getLang("cancel", "Cancel"),
                        id: "cancel"
                    }, {
                        title: ed.getLang("submit", "Submit"),
                        id: "submit",
                        onclick: function() {
                            var uploader, mimetype, value, image, filename = DOM.getValue(ed.id + "_blob_input");
                            return filename ? (filename = filename.replace(invalidCharacters, ""), 
                            invalidExtensions.test(filename) ? (showUploadError(ed.getLang("upload.file_extension_error", "File type not supported")), 
                            cancel()) : (uploader = function(blobInfo) {
                                var found;
                                return each(uploaders, function(instance) {
                                    var url = instance.getUploadURL({
                                        name: blobInfo.filename()
                                    });
                                    if (url) return !(found = {
                                        instance: instance,
                                        url: url
                                    });
                                }), found;
                            }(blobInfo)) ? (mimetype = DOM.getValue(ed.id + "_blob_mimetype") || (value = blobInfo.filename(), 
                            /\.(jpg|jpeg|png|gif|webp|avif)$/i.test(value) ? value.slice(value.lastIndexOf(".") + 1) : "") || "jpeg", 
                            value = DOM.getValue(ed.id + "_blob_quality") || 100, 
                            image = findMarker(marker), ed.setProgressState(!0), 
                            void function(blob, mimetype, quality) {
                                return new Promise(function(resolve) {
                                    var type = "image/" + mimetype;
                                    if (blob.type === type) return resolve(blob);
                                    var url = URL.createObjectURL(blob), image = new Image();
                                    image.onload = function() {
                                        var canvas = document.createElement("canvas");
                                        canvas.width = image.naturalWidth, canvas.height = image.naturalHeight, 
                                        canvas.getContext("2d").drawImage(image, 0, 0), 
                                        URL.revokeObjectURL(url), canvas.toBlob(function(converted) {
                                            resolve(converted || blob);
                                        }, type, (parseInt(quality, 10) || 100) / 100);
                                    }, image.onerror = function() {
                                        URL.revokeObjectURL(url), resolve(blob);
                                    }, image.src = url;
                                });
                            }(blobInfo.blob(), mimetype, value).then(function(blob) {
                                uploadHandler({
                                    method: "upload",
                                    id: Uuid.uuid("wf_"),
                                    inline: 1,
                                    name: filename + "." + mimetype,
                                    url: uploader.url + "&" + ed.settings.query,
                                    blob: blob
                                }, blobInfo, function(data) {
                                    image && (data.marker = image, function(uploader, data) {
                                        (uploader = uploader.insertUploadedFile(data)) && "IMG" === uploader.nodeName && (ed.selection.select(data.marker), 
                                        uploader.setAttribute("data-mce-tmp", "1"), 
                                        ed.execCommand("mceInsertContent", !1, ed.dom.getOuterHTML(uploader)), 
                                        each(ed.dom.select("[data-mce-tmp]"), function(node) {
                                            ed.selection.select(node), node.removeAttribute("data-mce-tmp");
                                        }));
                                    }(uploader.instance, data), ed.dom.remove(image)), 
                                    ed.setProgressState(!1), win.close(), resolve();
                                }, function(error) {
                                    showUploadError(error), ed.setProgressState(!1), 
                                    resolve();
                                }, function() {});
                            })) : cancel()) : cancel();
                        },
                        classes: "primary"
                    } ],
                    open: function() {
                        window.setTimeout(function() {
                            DOM.get(ed.id + "_blob_input").focus();
                        }, 10);
                    },
                    close: cancel
                });
            });
        }
        ed.onPreInit.add(function() {
            each(ed.plugins, function(plg) {
                var data;
                tinymce.is(plg.getUploadConfig, "function") && (data = plg.getUploadConfig()).inline && data.filetypes && uploaders.push(plg);
            });
        }), ed.onInit.add(function() {
            ed.undoManager.onBeforeAdd.add(function(um, level) {
                level.content = removePendingImages(level.content);
            }), ed.onPasteBeforeInsert.add(function(ed, o) {
                var o = ed.dom.create("div", 0, o.content), images = tinymce.grep(ed.dom.select("img[src]", o), isUploadableImage);
                images.length && (each(images, function(img) {
                    pending[img.getAttribute("src")] = !0;
                }), processImages(images).then(function(result) {
                    return result.reduce(function(promise, item, index) {
                        function done() {
                            delete pending[images[index].getAttribute("src")];
                        }
                        return promise.then(function() {
                            if (item) {
                                var image = findMarker(item.image);
                                if (image) return ed.selection.select(image), ed.selection.scrollIntoView(), 
                                uploadPastedImage(item.image, item.blobInfo);
                            }
                        }).then(done, done);
                    }, Promise.resolve());
                }));
            });
        });
    });
}();