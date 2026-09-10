/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var AutoLinkPattern = /^(https?:\/\/|ssh:\/\/|ftp:\/\/|file:\/|www\.|(?:mailto:)?[A-Z0-9._%+\-]+@)(.+)$/i;
    tinymce.PluginManager.add("autolink", function(ed, url) {
        function parseCurrentLine(editor, endOffset, delimiter) {
            var rng, end, endContainer, len, rngText, prev;
            function scopeIndex(container, index) {
                return index < 0 && (index = 0), index = 3 == container.nodeType && (container = container.data.length) < index ? container : index;
            }
            function setStart(container, offset) {
                1 != container.nodeType || container.hasChildNodes() ? rng.setStart(container, scopeIndex(container, offset)) : rng.setStartBefore(container);
            }
            function setEnd(container, offset) {
                1 != container.nodeType || container.hasChildNodes() ? rng.setEnd(container, scopeIndex(container, offset)) : rng.setEndAfter(container);
            }
            if ("A" != editor.selection.getNode().tagName) {
                if ((rng = editor.selection.getRng(!0).cloneRange()).startOffset < 5) {
                    if (!(prev = rng.endContainer.previousSibling)) {
                        if (!rng.endContainer.firstChild || !rng.endContainer.firstChild.nextSibling) return;
                        prev = rng.endContainer.firstChild.nextSibling;
                    }
                    if (setStart(prev, len = prev.length), setEnd(prev, len), rng.endOffset < 5) return;
                    end = rng.endOffset, endContainer = prev;
                } else {
                    if (3 != (endContainer = rng.endContainer).nodeType && endContainer.firstChild) {
                        for (;3 != endContainer.nodeType && endContainer.firstChild; ) endContainer = endContainer.firstChild;
                        3 == endContainer.nodeType && (setStart(endContainer, 0), 
                        setEnd(endContainer, endContainer.nodeValue.length));
                    }
                    end = 1 == rng.endOffset ? 2 : rng.endOffset - 1 - endOffset;
                }
                for (len = end; setStart(endContainer, 2 <= end ? end - 2 : 0), 
                setEnd(endContainer, 1 <= end ? end - 1 : 0), --end, " " != (rngText = rng.toString()) && "" !== rngText && 160 != rngText.charCodeAt(0) && 0 <= end - 2 && rngText != delimiter; );
                rng.toString() == delimiter || 160 == rng.toString().charCodeAt(0) ? (setStart(endContainer, end), 
                setEnd(endContainer, len), end += 1) : (0 === rng.startOffset ? setStart(endContainer, 0) : setStart(endContainer, end), 
                setEnd(endContainer, len)), "." == (prev = rng.toString()).charAt(prev.length - 1) && setEnd(endContainer, len - 1), 
                (endOffset = (prev = rng.toString()).match(AutoLinkPattern)) && ("www." == endOffset[1] ? endOffset[1] = "https://www." : /@$/.test(endOffset[1]) && !/^mailto:/.test(endOffset[1]) && (endOffset[1] = "mailto:" + endOffset[1]), 
                -1 !== endOffset[1].indexOf("http") && !editor.getParam("autolink_url", !0) || -1 !== endOffset[1].indexOf("mailto:") && !editor.getParam("autolink_email", !0) || (len = editor.selection.getBookmark(), 
                editor.selection.setRng(rng), editor.execCommand("createlink", !1, endOffset[1] + endOffset[2]), 
                prev = editor.selection.getNode(), editor.settings.default_link_target && editor.dom.setAttrib(prev, "target", editor.settings.default_link_target), 
                editor.onAutoLink.dispatch(editor, {
                    node: prev
                }), editor.selection.moveToBookmark(len), editor.nodeChanged()));
            }
        }
        (ed.getParam("autolink_url", !0) || ed.getParam("autolink_email", !0)) && (ed.settings.autolink_pattern && (AutoLinkPattern = ed.settings.autolink_pattern), 
        ed.onAutoLink = new tinymce.util.Dispatcher(this), ed.onKeyDown.addToTop(function(ed, e) {
            if (13 == e.keyCode) return function(ed) {
                parseCurrentLine(ed, -1, "");
            }(ed);
        }), tinymce.isIE || (ed.onKeyPress.add(function(ed, e) {
            if (41 == e.which) return function(ed) {
                parseCurrentLine(ed, -1, "(");
            }(ed);
        }), ed.onKeyUp.add(function(ed, e) {
            if (32 == e.keyCode) return function(ed) {
                parseCurrentLine(ed, 0, "");
            }(ed);
        })));
    });
}();