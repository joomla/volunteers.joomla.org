/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    "use strict";
    const Node$1 = tinymce.html.Node;
    var Content = {
        createTextNode: function(value, raw) {
            var text = new Node$1("#text", 3);
            return text.raw = !1 !== raw, text.value = value, text;
        },
        createShortcodeHtml: function(editor, data, tag) {
            return data = (data = editor.dom.decode(data)).replace(/[\n\r]/gi, "<br />"), 
            editor.dom.createHTML(tag || "pre", {
                "data-mce-code": "shortcode"
            }, editor.dom.encode(data));
        },
        createHtml: function(editor, data, type, tag) {
            return type = type || "script", tag = tag || "pre", !1 !== editor.settings.code_use_blocks ? editor.dom.createHTML(tag, {
                "data-mce-code": type
            }, editor.dom.encode(data)) : (data = data.replace(/<br[^>]*?>/gi, "\n"), 
            editor.dom.createHTML("img", {
                src: "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",
                "data-mce-resize": "false",
                "data-mce-code": type,
                "data-mce-type": "placeholder",
                "data-mce-value": escape(data)
            }));
        }
    };
    const each$1 = tinymce.each;
    let htmlSchema, shortEndedElements = {}, booleanAttributes = {};
    function canKeepCode(editor, type) {
        return !1 === editor.settings.validate || editor.getParam("code_allow_" + type);
    }
    function isXmlElement(editor, name) {
        return !htmlSchema.isValid(name) && !function(editor, name) {
            return editor = editor.settings.invalid_elements.split(","), -1 !== tinymce.inArray(editor, name);
        }(editor, name);
    }
    function isValid(editor, tag, attr) {
        return isXmlElement(editor, tag) || !1 === editor.settings.validate || editor.schema.isValid(tag, attr);
    }
    function validateXml(editor, xml) {
        return function sanitizeNode(editor, node) {
            var html = [];
            switch (node.nodeType) {
              case 1:
                var name, value, tagName = node.nodeName.toLowerCase();
                if (!isValid(editor, tagName)) return "";
                html.push("<", tagName);
                for ({
                    name,
                    value
                } of Array.from(node.attributes)) !isValid(editor, tagName, name) || !editor.settings.allow_event_attributes && name.startsWith("on") || (!booleanAttributes[name] || "" !== value && "true" !== value && value !== name ? html.push(" ", name, '="', editor.dom.encode(value, !0), '"') : html.push(" ", name));
                if (shortEndedElements[tagName]) "html5-strict" === editor.settings.schema ? html.push(">") : html.push(" />"); else {
                    html.push(">");
                    for (var child of Array.from(node.childNodes)) html.push(sanitizeNode(editor, child));
                    html.push("</", tagName, ">");
                }
                break;

              case 3:
                var text = node.nodeValue;
                html.push(text);
                break;

              case 5:
                html.push("<![CDATA[", editor.dom.encode(node.nodeValue, !0), "]]>");
                break;

              case 8:
                html.push("\x3c!--", editor.dom.encode(node.nodeValue, !0), "--\x3e");
            }
            return html.join("");
        }(editor, new DOMParser().parseFromString(xml, "text/xml").documentElement);
    }
    function processXML(editor, content) {
        return content.replace(/<([a-z0-9\-_\:\.]+)(?:[^>]*?)\/?>((?:[\s\S]*?)<\/\1>)?/gi, function(match, tag) {
            return ("svg" !== (tag = tag.toLowerCase()) || !1 !== editor.settings.code_allow_svg_in_xml) && ("math" !== tag || !1 !== editor.settings.code_allow_mathml_in_xml) && isXmlElement(editor, tag) ? (!1 !== editor.settings.code_validate_xml && (match = validateXml(editor, match)), 
            Content.createHtml(editor, match, "xml")) : match;
        });
    }
    function processShortcode(editor, html, tagName) {
        var attrPlaceholders;
        return -1 === html.indexOf("{") || "{" == html.charAt(0) && html.length < 3 || (-1 != html.indexOf("{/source}") && (html = function(editor, html) {
            return -1 === html.indexOf("{/source}") ? html : html.replace(/(?:(<(code|pre|samp|span)[^>]*(data-mce-type="code")?>|")?)\{source(.*?)\}([\s\S]+?)\{\/source\}/g, function(match) {
                return "<" === match.charAt(0) || '"' === match.charAt(0) ? match : (match = editor.dom.decode(match), 
                '<pre data-mce-code="shortcode" data-mce-label="sourcerer">' + editor.dom.encode(match) + "</pre>");
            });
        }(editor, html)), tagName = tagName || "span", attrPlaceholders = [], html = (html = html.replace(/=("[^"]*\{[^"]*"|'[^']*\{[^']*')/g, function(match) {
            return attrPlaceholders.push(match), '="__SHORTCODE_ATTR_' + (attrPlaceholders.length - 1) + '__"';
        })).replace(/(?:(<(code|pre|samp|span)[^>]*(data-mce-type="code")?>)?)(?:\{)([\w-]+)(.*?)(?:\/?\})(?:([\s\S]+?)\{\/\4\})?/g, function(match) {
            return "<" === match.charAt(0) ? match : Content.createShortcodeHtml(editor, match, tagName);
        }), attrPlaceholders.length && (html = html.replace(/="__SHORTCODE_ATTR_(\d+)__"/g, function(_match, index) {
            return attrPlaceholders[parseInt(index, 10)];
        }))), html;
    }
    function processPhp(editor, content) {
        return canKeepCode(editor, "php") ? (content = content.replace(/\="([^"]+?)"/g, function(_a, b) {
            return '="' + (b = b.replace(/<\?(php)?(.+?)\?>/gi, function(_x, _y, z) {
                return "__php_start__" + editor.dom.encode(z) + "__php_end__";
            })) + '"';
        }), (content = (content = /<textarea/.test(content) ? content.replace(/<textarea([^>]*)>([\s\S]*?)<\/textarea>/gi, function(_a, b, c) {
            return "<textarea" + b + ">" + (c = c.replace(/<\?(php)?(.+?)\?>/gi, function(_x, _y, z) {
                return "__php_start__" + editor.dom.encode(z) + "__php_end__";
            })) + "</textarea>";
        }) : content).replace(/<([^>]+)<\?(php)?(.+?)\?>([^>]*?)>/gi, function(_a, b, _c, d, e) {
            return " " !== b.charAt(b.length) && (b += " "), "<" + b + 'data-mce-php="' + d + '" ' + e + ">";
        })).replace(/<\?(php)?([\s\S]+?)\?>/gi, function(match) {
            return match = match.replace(/\n/g, "<br />"), Content.createHtml(editor, match, "php");
        })) : content.replace(/<\?(php)?([\s\S]*?)\?>/gi, "");
    }
    var Process = {
        init: function(editor) {
            htmlSchema = new tinymce.html.Schema({
                schema: "mixed",
                invalid_elements: editor.settings.invalid_elements
            }), each$1(editor.schema.getShortEndedElements(), function(_shortEnded, name) {
                shortEndedElements[name.toLowerCase()] = !0;
            }), each$1(editor.schema.getBoolAttrs(), function(_boolAttr, name) {
                booleanAttributes[name.toLowerCase()] = !0;
            });
        },
        processOnInsert: function(editor, value, _node) {
            return /\{.+\}/gi.test(value) && editor.settings.code_protect_shortcode && (value = processShortcode(editor, value, void 0)), 
            canKeepCode(editor, "custom_xml") && (value = processXML(editor, value)), 
            /<(\?|script|style)/.test(value) && (value = value.replace(/<(script|style)([^>]*?)>([\s\S]*?)<\/\1>/gi, function(match, type) {
                return canKeepCode(editor, type) ? (match = match.replace(/<br[^>]*?>/gi, "\n"), 
                Content.createHtml(editor, match, type)) : "";
            }), value = processPhp(editor, value)), value = /<link[^>]*?rel="stylesheet"[^>]*?>/gi.test(value) ? value.replace(/<link[^>]*?rel="stylesheet"[^>]*?>/gi, function(match) {
                return canKeepCode(editor, "style") ? Content.createHtml(editor, match, "link") : "";
            }) : value;
        },
        processShortcode: processShortcode,
        processPhp: processPhp,
        processXML: processXML
    };
    const each = tinymce.each, Node = tinymce.html.Node, VK = tinymce.VK, DomParser = tinymce.html.DomParser, Serializer = tinymce.html.Serializer;
    function isOnlyChild(node) {
        var child = node.parent.firstChild, count = 0;
        if (child) do {
            if (1 === child.type) {
                if (child.attributes.map["data-mce-type"] || child.attributes.map["data-mce-bogus"]) continue;
                if (child === node) continue;
                count++;
            }
            8 === child.type && count++, 3 !== child.type || /^[ \t\r\n]*$/.test(child.value) || count++;
        } while (child = child.next);
        return 0 === count;
    }
    tinymce.PluginManager.add("code", function(editor, url) {
        function canKeepCode(type) {
            return !1 === editor.settings.validate || !!editor.getParam("code_allow_" + type);
        }
        var blockElements = [], inlineElements = [], code_blocks = !1 !== editor.settings.code_use_blocks;
        function handleEnterInPre(ed, node, before) {
            var node = ed.dom.getParents(node, blockElements.join(",")), newBlockName = ed.settings.forced_root_block || "p", node = (!1 === ed.settings.force_block_newlines && (newBlockName = "br"), 
            node.shift());
            node !== ed.getBody() && (newBlockName = ed.dom.create(newBlockName, {}, "\xa0"), 
            before ? node.parentNode.insertBefore(newBlockName, node) : ed.dom.insertAfter(newBlockName, node), 
            (before = ed.selection.getRng()).setStart(newBlockName, 0), before.setEnd(newBlockName, 0), 
            ed.selection.setRng(before), ed.selection.scrollIntoView(newBlockName));
        }
        editor.settings.code_allow_script && (editor.settings.allow_script_urls = !0), 
        editor.addCommand("InsertShortCode", function(ui, html) {
            return editor.settings.code_protect_shortcode && (html = Process.processShortcode(editor, html, "pre"), 
            tinymce.is(html)) && editor.execCommand("mceReplaceContent", !1, html), 
            !1;
        }), editor.onKeyDown.add(function(ed, e) {
            var node;
            if (e.keyCode == VK.ENTER) {
                if ("PRE" === (node = ed.selection.getNode()).nodeName && "shortcode" === node.getAttribute("data-mce-code")) return void (e.shiftKey || (ed.execCommand("InsertLineBreak", !1, e), 
                e.preventDefault()));
                "SPAN" === node.nodeName && node.getAttribute("data-mce-code") && (handleEnterInPre(ed, node), 
                e.preventDefault());
            }
            e.keyCode == VK.UP && e.altKey && "PRE" == (node = ed.selection.getNode()).nodeName && (handleEnterInPre(ed, node, !0), 
            e.preventDefault()), 9 != e.keyCode || VK.metaKeyPressed(e) || "PRE" === (node = ed.selection.getNode()).nodeName && node.getAttribute("data-mce-code") && (ed.selection.setContent("\t", {
                no_events: !0
            }), e.preventDefault()), e.keyCode !== VK.BACKSPACE && e.keyCode !== VK.DELETE || "SPAN" === (node = ed.selection.getNode()).nodeName && node.getAttribute("data-mce-code") && "placeholder" === node.getAttribute("data-mce-type") && (ed.undoManager.add(), 
            ed.dom.remove(node), e.preventDefault());
        }), editor.onPreInit.add(function() {
            function isCodePlaceholder(node) {
                return "SPAN" === node.nodeName && node.getAttribute("data-mce-code") && "placeholder" == node.getAttribute("data-mce-type");
            }
            Process.init(editor), editor.dom.bind(editor.getDoc(), "keyup click", function(e) {
                var node = e.target, sel = editor.selection.getNode();
                editor.dom.removeClass(editor.dom.select(".mce-item-selected"), "mce-item-selected"), 
                node === editor.getBody() && isCodePlaceholder(sel) ? sel.parentNode !== node || sel.nextSibling || editor.dom.insertAfter(editor.dom.create("br", {
                    "data-mce-bogus": 1
                }), sel) : isCodePlaceholder(node) && (e.preventDefault(), e.stopImmediatePropagation(), 
                editor.selection.select(node), window.setTimeout(function() {
                    editor.dom.addClass(node, "mce-item-selected");
                }, 10), e.preventDefault());
            });
            function onSetContent() {
                each(editor.dom.select("pre[data-mce-code]", editor.getBody()), function(elm) {
                    var clone, clonedElm, elm = editor.dom.getParent(elm, "p");
                    elm && ((clonedElm = (clone = elm.cloneNode(!0)).querySelector("[data-mce-code]")) && clone.removeChild(clonedElm), 
                    editor.dom.isEmpty(clone)) && editor.dom.remove(elm, 1);
                });
            }
            var ctrl = editor.controlManager.get("formatselect");
            ctrl && each([ "script", "style", "php", "shortcode", "xml" ], function(key) {
                var title = editor.getLang("code." + key, key);
                if ("shortcode" === key && editor.settings.code_protect_shortcode) return ctrl.add(title, key, {
                    class: "mce-code-" + key
                }), editor.formatter.register("shortcode", {
                    block: "pre",
                    attributes: {
                        "data-mce-code": "shortcode"
                    }
                }), !0;
                "xml" === key && (editor.settings.code_allow_xml = !!editor.settings.code_allow_custom_xml), 
                canKeepCode(key) && code_blocks && (ctrl.add(title, key, {
                    class: "mce-code-" + key
                }), editor.formatter.register(key, {
                    block: "pre",
                    attributes: {
                        "data-mce-code": key
                    },
                    onformat: function(elm) {
                        each(editor.dom.select("br", elm), function(br) {
                            editor.dom.replace(editor.dom.doc.createTextNode("\n"), br);
                        });
                    }
                }));
            }), each(editor.schema.getBlockElements(), function(_block, blockName) {
                blockElements.push(blockName);
            }), each(editor.schema.getTextInlineElements(), function(_inline, name) {
                inlineElements.push(name);
            }), editor.settings.code_protect_shortcode && (editor.textpattern.addPattern({
                start: "{",
                end: "}",
                cmd: "InsertShortCode",
                remove: !0
            }), editor.textpattern.addPattern({
                start: " {",
                end: "}",
                format: "inline-shortcode",
                remove: !1
            })), editor.formatter.register("inline-shortcode", {
                inline: "span",
                attributes: {
                    "data-mce-code": "shortcode"
                }
            }), editor.selection.onBeforeSetContent.addToTop(function(sel, o) {
                sel = sel.getNode();
                sel && "PRE" === sel.nodeName || (o.content = Process.processOnInsert(editor, o.content, sel));
            });
            editor.onSetContent.add(onSetContent), editor.selection.onSetContent.add(onSetContent), 
            editor.parser.addNodeFilter("script,style,link", function(nodes) {
                for (var i = nodes.length; i--; ) {
                    var node, text, value, placeholder, parent = (node = nodes[i]).parent;
                    parent && "pre" === parent.name || ("link" == node.name && "stylesheet" != node.attr("rel") ? node.remove() : (node.attr("data-mce-fragment", null), 
                    node.firstChild && (node.firstChild.value = node.firstChild.value.replace(/<span([^>]+)>([\s\S]+?)<\/span>/gi, function(match, attr, content) {
                        return -1 === attr.indexOf("data-mce-code") ? match : editor.dom.decode(content);
                    })), code_blocks ? (value = new Serializer({
                        validate: !1
                    }).serialize(node), value = tinymce.trim(value), (parent = new Node("pre", 1)).attr({
                        "data-mce-code": node.name
                    }), text = Content.createTextNode(value, !1), parent.append(text), 
                    node.replace(parent)) : (value = "", node.firstChild && (value = tinymce.trim(node.firstChild.value)), 
                    placeholder = Node.create("img", {
                        src: "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",
                        "data-mce-code": node.name,
                        "data-mce-type": "placeholder",
                        "data-mce-resize": "false",
                        title: editor.dom.encode(value)
                    }), each(node.attributes, function(attr) {
                        placeholder.attr("data-mce-p-" + attr.name, attr.value);
                    }), value && placeholder.attr("data-mce-value", escape(value)), 
                    node.replace(placeholder))));
                }
            }), editor.parser.addAttributeFilter("data-mce-code", function(nodes, name) {
                var node, i = nodes.length;
                function isBlockNode(node) {
                    return -1 != tinymce.inArray(blockElements, node.name);
                }
                for (;i--; ) {
                    var type, parent = (node = nodes[i]).parent;
                    "placeholder" == node.attr("data-mce-type") || "shortcode" !== (type = node.attr(name)) && "php" !== type || ((type = node.firstChild.value) && (node.firstChild.value = type.replace(/<br[\s\/]*>/g, "\n")), 
                    parent && (parent.attr(name) ? node.unwrap() : ("body" !== parent.name && !isOnlyChild(node) && function(node) {
                        return "span" == node.name && (node.next && ("#text" == node.next.type || !isBlockNode(node.next)) || node.prev && ("#text" == node.prev.type || !isBlockNode(node.prev)) || !(!node.parent || isBlockNode(node.parent)));
                    }(node) || (node.name = "pre", node.parent && function(node) {
                        return -1 != tinymce.inArray(inlineElements, node.name);
                    }(node.parent) && (node.name = "span"), "pre" == node.name && parent && "p" == parent.name && isOnlyChild(node) && parent.parent && parent.replace(node)), 
                    "span" == node.name && node === parent.lastChild && (type = Content.createTextNode("\xa0"), 
                    parent.append(type)))));
                }
            }), editor.serializer.addAttributeFilter("data-mce-code", function(nodes, name) {
                var i = nodes.length;
                for (;i--; ) {
                    var node, root_block = !1, type = (node = nodes[i]).attr(name);
                    if ("img" === node.name) {
                        var key, elm = new Node(type, 1);
                        for (key in node.attributes.map) {
                            var val = node.attributes.map[key];
                            -1 !== key.indexOf("data-mce-p-") ? key = key.substr(11) : val = null, 
                            elm.attr(key, val);
                        }
                        var imgValue = node.attr("data-mce-value");
                        imgValue && (imgValue = Content.createTextNode(unescape(imgValue)), 
                        "php" == type || "shortcode" == type ? elm = imgValue : elm.append(imgValue)), 
                        node.replace(elm);
                    } else if (node.isEmpty() && node.remove(), "xml" !== type) {
                        "script" !== type && "style" !== type || (root_block = type);
                        var childVal, parser, child = node.firstChild, imgValue = node.clone(!0), text = "";
                        if (child) do {} while (/(shortcode|php)/.test(node.attr("data-mce-code")) || (childVal = "br" == child.name ? "\n" : child.value) && (text += childVal), 
                        child = child.next);
                        text && (imgValue.empty(), parser = new DomParser({
                            validate: !1
                        }), "script" !== type && "style" !== type || parser.addNodeFilter(type, function(items, filterName) {
                            for (var n = items.length; n--; ) {
                                var item = items[n];
                                each(item.attributes, function(attr) {
                                    return !attr || 0 === attr.name.indexOf("data-") && -1 === attr.name.indexOf("data-mce-") || void (!1 === editor.schema.isValid(filterName, attr.name) && item.attr(attr.name, null));
                                });
                            }
                        }), parser = parser.parse(text, {
                            forced_root_block: root_block
                        }), imgValue.append(parser)), node.replace(imgValue), "shortcode" === type && "pre" === imgValue.name && (root_block = Content.createTextNode("\n"), 
                        imgValue.append(root_block), imgValue.unwrap());
                    }
                }
            }), editor.onContextMenu.addToTop(function(ed, e) {
                ed = ed.selection.getNode();
                if (ed && ed.hasAttribute("data-mce-code")) return !1;
            });
        }), editor.onInit.add(function() {
            editor.theme && editor.theme.onResolveName && editor.theme.onResolveName.add(function(theme, o) {
                var node = o.node;
                node.getAttribute("data-mce-code") && (o.name = node.getAttribute("data-mce-code"));
            });
        });
        editor.onMouseDown.add(function(ed, e) {
            var clientY, top, right, pre = e.target.closest("pre[data-mce-code]");
            pre && ({
                clientX: e,
                clientY
            } = e, {
                top,
                right
            } = pre.getBoundingClientRect(), right - 32 <= e) && clientY <= top + 32 && ed.dom.toggleClass(pre, "mce-code-toggle");
        }), editor.onBeforeSetContent.addToTop(function(ed, o) {
            editor.settings.code_protect_shortcode && -1 === o.content.indexOf('data-mce-code="shortcode"') && (o.content = Process.processShortcode(editor, o.content)), 
            canKeepCode("custom_xml") && o.content && o.load && (o.content = Process.processXML(editor, o.content)), 
            /<(\?|script|style|link)/.test(o.content) && (canKeepCode("script") || (o.content = o.content.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, "")), 
            canKeepCode("style") || (o.content = o.content.replace(/<style[^>]*>([\s\S]*?)<\/style>/gi, ""), 
            o.content = o.content.replace(/<link[^>]*?rel="stylesheet"[^>]*?>/gi, "")), 
            o.content = Process.processPhp(editor, o.content));
        }), editor.onPostProcess.add(function(ed, o) {
            o.get && (/(data-mce-php|__php_start__)/.test(o.content) && (o.content = o.content.replace(/({source})?__php_start__(.*?)__php_end__/g, function(match, pre, code) {
                return (pre || "") + "<?php" + ed.dom.decode(code) + "?>";
            }), o.content = o.content.replace(/<textarea([^>]*)>([\s\S]*?)<\/textarea>/gi, function(a, b, c) {
                return "<textarea" + b + ">" + (c = /&lt;\?php/.test(c) ? ed.dom.decode(c) : c) + "</textarea>";
            }), o.content = o.content.replace(/data-mce-php="([^"]+?)"/g, function(a, b) {
                return "<?php" + ed.dom.decode(b) + "?>";
            })), editor.settings.code_protect_shortcode && (o.content = o.content.replace(/\{([\s\S]+?)\}/gi, function(match, content) {
                return "{" + ed.dom.decode(content) + "}";
            }), o.content = o.content.replace(/\{source([^\}]*?)\}([\s\S]+?)\{\/source\}/gi, function(match, start, content) {
                return "{source" + start + "}" + ed.dom.decode(content) + "{/source}";
            }), o.content = o.content.replace(/\{([\w-]+)(.*?)\}([\s\S]+)\{\/\1\}/gi, function(match, start, attr, content) {
                return "{" + start + attr + "}" + ed.dom.decode(content) + "{/" + start + "}";
            })), o.content = o.content.replace(/<(pre|span)([^>]+?)>([\s\S]*?)<\/\1>/gi, function(match, tag, attr, content) {
                if (-1 === attr.indexOf("data-mce-code")) return match;
                content = tinymce.trim(content);
                attr = ed.dom.create("div", {}, match).firstChild.getAttribute("data-mce-code");
                return "script" != attr && (content = content.replace(/<br[^>]*?>/gi, "\n")), 
                content = ed.dom.decode(content), "php" == attr && (content = content.replace(/<\?(php)?/gi, "").replace(/\?>/g, ""), 
                content = "<?php\n" + tinymce.trim(content) + "\n?>"), content;
            }), o.content = o.content.replace(/<!--mce:protected ([\s\S]+?)-->/gi, function(match, content) {
                return unescape(content);
            }));
        });
    });
}();