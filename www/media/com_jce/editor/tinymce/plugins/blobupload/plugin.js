/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, BlobCache = tinymce.file.BlobCache, Conversions = tinymce.file.Conversions, Uuid = tinymce.util.Uuid, DOM = tinymce.DOM, count = 0, uniqueId = function(prefix) {
        return (prefix || "blobid") + count++;
    };
    tinymce.PluginManager.add("blobupload", function(ed, url) {
        var uploaders = [];
        function findMarker(marker) {
            var found;
            return each(ed.dom.select("img[src]"), function(image) {
                if (image.src == marker.src) return found = image, !1;
            }), found;
        }
        function removeMarker(marker) {
            each(ed.dom.select("img[src]"), function(image) {
                image.src == marker.src && (ed.selection.select(image), ed.execCommand("mceRemoveNode"), 
                "P" == (image = ed.selection.getNode()).nodeName) && ed.dom.isEmpty(image) && ed.dom.add(image, "br", {
                    "data-mce-bogus": 1
                });
            });
        }
        function processImages(images) {
            var cachedPromises = {}, images = tinymce.map(images, function(img) {
                var newPromise;
                return cachedPromises[img.src] ? new Promise(function(resolve) {
                    cachedPromises[img.src].then(function(imageInfo) {
                        if ("string" == typeof imageInfo) return imageInfo;
                        resolve({
                            image: img,
                            blobInfo: imageInfo.blobInfo
                        });
                    });
                }) : (newPromise = new Promise(function(resolve, reject) {
                    !function(blobCache, img, resolve, reject) {
                        var base64, blobInfo;
                        0 === img.src.indexOf("blob:") ? (blobInfo = blobCache.getByUri(img.src)) ? resolve({
                            image: img,
                            blobInfo: blobInfo
                        }) : Conversions.uriToBlob(img.src).then(function(blob) {
                            Conversions.blobToDataUri(blob).then(function(dataUri) {
                                base64 = Conversions.parseDataUri(dataUri).data, 
                                blobInfo = blobCache.create(uniqueId(), blob, base64), 
                                blobCache.add(blobInfo), resolve({
                                    image: img,
                                    blobInfo: blobInfo
                                });
                            });
                        }, function(err) {
                            reject(err);
                        }) : (base64 = Conversions.parseDataUri(img.src).data, (blobInfo = blobCache.findFirst(function(cachedBlobInfo) {
                            return cachedBlobInfo.base64() === base64;
                        })) ? resolve({
                            image: img,
                            blobInfo: blobInfo
                        }) : Conversions.uriToBlob(img.src).then(function(blob) {
                            blobInfo = blobCache.create(uniqueId(), blob, base64), 
                            blobCache.add(blobInfo), resolve({
                                image: img,
                                blobInfo: blobInfo
                            });
                        }, function(err) {
                            reject(err);
                        }));
                    }(BlobCache, img, resolve, reject);
                }).then(function(result) {
                    return delete cachedPromises[result.image.src], result;
                }).catch(function(error) {
                    return delete cachedPromises[img.src], error;
                }), cachedPromises[img.src] = newPromise);
            });
            return Promise.all(images);
        }
        function uploadPastedImage(marker, blobInfo) {
            return new Promise(function(resolve, reject) {
                if (!uploaders.length) return removeMarker(marker), resolve();
                var html = '<div class="mceForm"><p>' + ed.getLang("upload.name_description", "Please supply a name for this file") + '</p><div class="mceModalRow">   <label for="' + ed.id + '_blob_input">' + ed.getLang("dlg.name", "Name") + '</label>   <div class="mceModalControl mceModalControlAppend">       <input type="text" id="' + ed.id + '_blob_input" autofocus />       <select id="' + ed.id + '_blob_mimetype">           <option value="jpeg">jpeg</option>           <option value="png">png</option>       </select>   </div></div><div class="mceModalRow">   <label for="' + ed.id + '_blob_input">' + ed.getLang("dlg.quality", "Quality") + '</label>   <div class="mceModalControl">       <select id="' + ed.id + '_blob_quality" class="mce-flex-25">           <option value="100">100</option>           <option value="90">90</option>           <option value="80">80</option>           <option value="70">70</option>           <option value="60">60</option>           <option value="50">50</option>           <option value="40">40</option>           <option value="30">30</option>           <option value="20">20</option>           <option value="10">10</option>       </select>       <span role="presentation">%</span>   </div></div></div>', win = ed.windowManager.open({
                    title: ed.getLang("dlg.name", "Name"),
                    content: html,
                    size: "mce-modal-landscape-small",
                    buttons: [ {
                        title: ed.getLang("cancel", "Cancel"),
                        id: "cancel"
                    }, {
                        title: ed.getLang("submit", "Submit"),
                        id: "submit",
                        onclick: function(e) {
                            var url, uploader, quality, value, images, filename = DOM.getValue(ed.id + "_blob_input");
                            return filename ? (filename = filename.replace(/[\+\\\/\?\#%&<>"\'=\[\]\{\},;@\^\(\)\xa3\u20ac$~]/g, ""), 
                            /\.(php([0-9]*)|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)\b/i.test(filename) ? (ed.windowManager.alert({
                                text: ed.getLang("upload.file_extension_error", "File type not supported"),
                                title: ed.getLang("upload.error", "Upload Error")
                            }), removeMarker(marker), resolve()) : (each(uploaders, function(instance) {
                                if (!url && (url = instance.getUploadURL({
                                    name: blobInfo.filename()
                                }))) return uploader = instance, !1;
                            }), url ? (value = blobInfo.filename(), value = (/\.(jpg|jpeg|png|gif|webp|avif)$/.test(value) ? value.substring(value.length, value.lastIndexOf(".") + 1) : "") || "jpeg", 
                            quality = DOM.getValue(ed.id + "_blob_quality") || 100, 
                            value = DOM.getValue(ed.id + "_blob_mimetype") || value, 
                            filename = {
                                method: "upload",
                                id: Uuid.uuid("wf_"),
                                inline: 1,
                                name: filename,
                                url: url + "&" + ed.settings.query,
                                mimetype: "image/" + value,
                                quality: quality
                            }, images = tinymce.grep(ed.dom.select("img[src]"), function(image) {
                                return image.src == marker.src;
                            }), ed.setProgressState(!0), void function(settings, blobInfo, success, failure, progress) {
                                var formData, xhr = new XMLHttpRequest();
                                xhr.open("POST", settings.url), xhr.upload.onprogress = function(e) {
                                    progress(e.loaded / e.total * 100);
                                }, xhr.onerror = function() {
                                    failure("Image upload failed due to a XHR Transport error. Code: " + xhr.status);
                                }, xhr.onload = function() {
                                    var json;
                                    xhr.status < 200 || 300 <= xhr.status ? failure("HTTP Error: " + xhr.status) : (json = JSON.parse(xhr.responseText)) && !json.error && json.result && json.result.files ? success(json.result.files[0]) : failure(json.error.message || "Invalid JSON response!");
                                }, (formData = new FormData()).append("file", blobInfo.blob(), blobInfo.filename()), 
                                each(settings, function(value, name) {
                                    if ("url" == name || "multipart" == name) return !0;
                                    formData.append(name, value);
                                }), xhr.send(formData);
                            }(filename, blobInfo, function(data) {
                                data.marker = images[0];
                                data = uploader.insertUploadedFile(data);
                                data && (ed.undoManager.add(), ed.dom.replace(data, images[0]), 
                                ed.selection.select(data)), ed.setProgressState(!1), 
                                win.close(), resolve();
                            }, function(error) {
                                ed.windowManager.alert({
                                    text: error,
                                    title: ed.getLang("upload.error", "Upload Error")
                                }), ed.setProgressState(!1), resolve();
                            }, function() {})) : (removeMarker(marker), resolve()))) : (removeMarker(marker), 
                            resolve());
                        },
                        classes: "primary"
                    } ],
                    open: function() {
                        window.setTimeout(function() {
                            DOM.get(ed.id + "_blob_input").focus();
                        }, 10);
                    },
                    close: function() {
                        return removeMarker(marker), resolve();
                    }
                });
            });
        }
        ed.onPreInit.add(function() {
            each(ed.plugins, function(plg, name) {
                var data;
                tinymce.is(plg.getUploadConfig, "function") && (data = plg.getUploadConfig()).inline && data.filetypes && uploaders.push(plg);
            });
        }), ed.onInit.add(function() {
            ed.onPasteBeforeInsert.add(function(ed, o) {
                var promises, o = ed.dom.create("div", 0, o.content), o = tinymce.grep(ed.dom.select("img[src]", o), function(img) {
                    var src = img.getAttribute("src");
                    return !(img.hasAttribute("data-mce-bogus") || img.hasAttribute("data-mce-placeholder") || img.hasAttribute("data-mce-upload-marker") || !src || "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" == src || 0 !== src.indexOf("blob:") && 0 !== src.indexOf("data:"));
                });
                o.length && (promises = [], processImages(o).then(function(result) {
                    each(result, function(item) {
                        "string" != typeof item && (ed.selection.select(findMarker(item.image)), 
                        ed.selection.scrollIntoView(), promises.push(uploadPastedImage(item.image, item.blobInfo)));
                    });
                }), Promise.all(promises).then());
            });
        });
    });
}();