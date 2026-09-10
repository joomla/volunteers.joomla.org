/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM;
    tinymce.PluginManager.add("noneditable", function(editor) {
        var editClass = tinymce.trim(editor.getParam("noneditable_editable_class", "mceEditable")), nonEditClass = tinymce.trim(editor.getParam("noneditable_noneditable_class", "mceNonEditable"));
        function hasClass(checkClassName) {
            return function(node) {
                return -1 !== (" " + node.attr("class") + " ").indexOf(checkClassName);
            };
        }
        var nonEditableRegExps, hasEditClass = hasClass(editClass), hasNonEditClass = hasClass(nonEditClass);
        (nonEditableRegExps = editor.getParam("noneditable_regexp")) && !nonEditableRegExps.length && (nonEditableRegExps = [ nonEditableRegExps ]), 
        editor.onPreInit.add(function() {
            editor.formatter.register("noneditable", {
                block: "div",
                wrapper: !0,
                onformat: function(elm, fmt, vars) {
                    tinymce.each(vars, function(value, key) {
                        editor.dom.setAttrib(elm, key, value);
                    });
                }
            }), nonEditableRegExps && editor.onBeforeSetContent.add(function(ed, e) {
                !function(e) {
                    var i = nonEditableRegExps.length, content = e.content, cls = tinymce.trim(nonEditClass);
                    function replaceMatchWithSpan(match) {
                        var args = arguments, index = args[args.length - 2], prevChar = 0 < index ? content.charAt(index - 1) : "";
                        if ('"' === prevChar) return match;
                        if (">" === prevChar) {
                            prevChar = content.lastIndexOf("<", index);
                            if (-1 !== prevChar) if (-1 !== content.substring(prevChar, index).indexOf('contenteditable="false"')) return match;
                        }
                        return '<span class="' + cls + '" data-mce-content="' + editor.dom.encode(args[0]) + '">' + editor.dom.encode("string" == typeof args[1] ? args[1] : args[0]) + "</span>";
                    }
                    if ("raw" != e.format) {
                        for (;i--; ) content = content.replace(nonEditableRegExps[i], replaceMatchWithSpan);
                        e.content = content;
                    }
                }(e);
            }), editor.parser.addAttributeFilter("class", function(nodes) {
                for (var node, i = nodes.length; i--; ) node = nodes[i], hasEditClass(node) ? node.attr("contenteditable", "true") : hasNonEditClass(node) && node.attr("contenteditable", "false");
            }), editor.serializer.addAttributeFilter("contenteditable", function(nodes) {
                for (var node, i = nodes.length; i--; ) node = nodes[i], (hasEditClass(node) || hasNonEditClass(node)) && (nonEditableRegExps && node.attr("data-mce-content") ? (node.name = "#text", 
                node.type = 3, node.raw = !0, node.value = node.attr("data-mce-content")) : node.attr("contenteditable", null));
            });
        }), this.isEditable = function(node) {
            return node.attr ? node.hasClass(editClass) : DOM.hasClass(node, editClass);
        }, this.isNonEditable = function(node) {
            return node.attr ? node.hasClass(nonEditClass) : DOM.hasClass(node, nonEditClass);
        };
    });
}();