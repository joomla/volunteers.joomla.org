/* jce - 2.9.99.12 | 2026-10-07 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html; see LICENSE.txt */
!function() {
    var Storage = tinymce.util.Storage;
    tinymce.PluginManager.add("visualchars", function(ed, url) {
        function toggleVisualChars(state, o) {
            var nodeList, i, rng, caretNode, caretOffset, caret, charMap, visualCharsRegExp, o = o || ed.getBody();
            function wrapTextNode(textNode, offset) {
                var match, span, value = textNode.nodeValue, doc = textNode.ownerDocument, frag = doc.createDocumentFragment(), last = 0, wrapped = !1, pos = null;
                function addText(start, end) {
                    var text = doc.createTextNode(value.substring(start, end));
                    frag.appendChild(text), start <= offset && offset <= end && !pos && (pos = {
                        node: text,
                        offset: offset - start
                    });
                }
                for (visualCharsRegExp.lastIndex = 0; match = visualCharsRegExp.exec(value); ) match.index !== offset - 1 && (match.index > last && addText(last, match.index), 
                span = ed.dom.create("span", {
                    "data-mce-bogus": "1",
                    class: "mce-item-" + charMap[match[0]]
                }, match[0]), frag.appendChild(span), 0 === offset && (pos = pos || {
                    before: span
                }), last = match.index + 1, wrapped = !0);
                return wrapped ? (last < value.length && addText(last, value.length), 
                textNode.parentNode.replaceChild(frag, textNode), pos) : null;
            }
            if (charMap = {
                "\xa0": "nbsp",
                "\xad": "shy"
            }, visualCharsRegExp = function() {
                var key, regExp = "";
                for (key in charMap) regExp += key;
                return new RegExp("[" + regExp + "]", "g");
            }(), state) {
                for (nodeList = [], tinymce.walk(o, function(n) {
                    visualCharsRegExp.lastIndex = 0, function(n) {
                        return 3 === n.nodeType && !ed.dom.getParent(n, ".mce-item-nbsp, .mce-item-shy");
                    }(n) && n.nodeValue && visualCharsRegExp.test(n.nodeValue) && nodeList.push(n);
                }, "childNodes"), ed.selection && ed.getBody().contains(o) && (rng = ed.selection.getRng(!0)) && rng.collapsed && 3 === rng.startContainer.nodeType && (caretNode = rng.startContainer, 
                caretOffset = rng.startOffset), i = 0; i < nodeList.length; i++) nodeList[i] === caretNode ? caret = wrapTextNode(nodeList[i], caretOffset) : wrapTextNode(nodeList[i], -1);
                caret && (rng = ed.dom.createRng(), caret.before ? rng.setStartBefore(caret.before) : rng.setStart(caret.node, caret.offset), 
                rng.collapse(!0), ed.selection.setRng(rng));
            } else for (i = (nodeList = ed.dom.select(function() {
                var key, selector = "";
                for (key in charMap) selector && (selector += ","), selector += "span.mce-item-" + charMap[key];
                return selector;
            }(), o)).length - 1; 0 <= i; i--) ed.dom.remove(nodeList[i], 1);
        }
        var state;
        function unwrapNbsp() {
            var span, rng = ed.selection.getRng(!0), start = rng.startContainer, caret = null, parents = [], block = ed.dom.getParent(start, ed.dom.isBlock) || ed.getBody(), block = ed.dom.select("span.mce-item-nbsp", block);
            block.length && ((span = ed.dom.getParent(start, "span.mce-item-nbsp")) && rng.collapsed && span.firstChild && (caret = start === span ? {
                node: span.firstChild,
                offset: rng.startOffset ? span.firstChild.nodeValue.length : 0
            } : {
                node: start,
                offset: rng.startOffset
            }), tinymce.each(block, function(span) {
                for (var parent = span.parentNode; span.firstChild; ) parent.insertBefore(span.firstChild, span);
                parent.removeChild(span), -1 === tinymce.inArray(parents, parent) && parents.push(parent);
            }), caret && ((rng = ed.dom.createRng()).setStart(caret.node, caret.offset), 
            rng.collapse(!0), ed.selection.setRng(rng)), tinymce.each(parents, function(parent) {
                parent.normalize();
            }));
        }
        ed.getParam("use_state_cookies", !0) && (state = Storage.get("wf_visualchars_state")), 
        state = tinymce.is(state, "string") ? parseFloat(state) : ed.getParam("visualchars_default_state", 0), 
        ed.onInit.add(function() {
            ed.controlManager.setActive("visualchars", state), toggleVisualChars(state);
        }), ed.addButton("visualchars", {
            title: "visualchars.desc",
            cmd: "mceVisualChars"
        }), ed.onExecCommand.add(function(ed, cmd, ui, v, o) {
            "mceNonBreaking" === cmd && toggleVisualChars(state);
        }), ed.addCommand("mceVisualChars", function() {
            state = !state, ed.controlManager.setActive("visualchars", state), toggleVisualChars(state), 
            ed.getParam("use_state_cookies", !0) && Storage.set("wf_visualchars_state", state ? 1 : 0);
        }, self), ed.onKeyDown.add(function(ed, e) {
            !state || 32 != e.keyCode && 8 != e.keyCode && 46 != e.keyCode || unwrapNbsp();
        }), ed.onKeyUp.add(function(ed, e) {
            state && (13 == e.keyCode && toggleVisualChars(state), 32 != e.keyCode && 8 != e.keyCode && 46 != e.keyCode || toggleVisualChars(state, ed.dom.getParent(ed.selection.getNode(), ed.dom.isBlock) || ed.getBody()));
        }), ed.onPreProcess.add(function(ed, o) {
            o.get && toggleVisualChars(!1, o.node);
        }), ed.onSetContent.add(function(ed, o) {
            toggleVisualChars(state);
        });
    });
}();