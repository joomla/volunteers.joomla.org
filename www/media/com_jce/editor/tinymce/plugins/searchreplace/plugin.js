/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM;
    function findAndReplaceDOMText(regex, node, replacementNode, captureGroup, schema) {
        var m, text, doc, blockElementsMap, hiddenTextElementsMap, shortEndedElementsMap, stencilNode, makeReplacementNode, matches = [], count = 0;
        function getMatchIndexes(m, captureGroup) {
            if (!m[0]) throw "findAndReplaceDOMText cannot handle zero-length matches";
            var index = m.index;
            if (0 < (captureGroup = captureGroup || 0)) {
                captureGroup = m[captureGroup];
                if (!captureGroup) throw "Invalid capture group";
                index += m[0].indexOf(captureGroup), m[0] = captureGroup;
            }
            return [ index, index + m[0].length, [ m[0] ] ];
        }
        if (doc = node.ownerDocument, blockElementsMap = schema.getBlockElements(), 
        hiddenTextElementsMap = schema.getWhiteSpaceElements(), shortEndedElementsMap = schema.getShortEndedElements(), 
        text = function getText(node) {
            var txt;
            if (3 === node.nodeType) return node.data;
            if (hiddenTextElementsMap[node.nodeName] && !blockElementsMap[node.nodeName]) return "";
            if (txt = "", (blockElementsMap[node.nodeName] || shortEndedElementsMap[node.nodeName]) && (txt += "\n"), 
            node = node.firstChild) for (;txt += getText(node), node = node.nextSibling; );
            return txt;
        }(node)) {
            if (regex.global) for (;m = regex.exec(text); ) matches.push(getMatchIndexes(m, captureGroup)); else m = text.match(regex), 
            matches.push(getMatchIndexes(m, captureGroup));
            return matches.length && (count = matches.length, function(node, matches, replaceFn) {
                var startNode, endNode, startNodeIndex, endNodeIndex, innerNodes = [], atIndex = 0, curNode = node, matchLocation = matches.shift(), matchIndex = 0;
                out: for (;;) {
                    if ((blockElementsMap[curNode.nodeName] || shortEndedElementsMap[curNode.nodeName]) && atIndex++, 
                    3 === curNode.nodeType && (!endNode && curNode.length + atIndex >= matchLocation[1] ? (endNode = curNode, 
                    endNodeIndex = matchLocation[1] - atIndex) : startNode && innerNodes.push(curNode), 
                    !startNode && curNode.length + atIndex > matchLocation[0] && (startNode = curNode, 
                    startNodeIndex = matchLocation[0] - atIndex), atIndex += curNode.length), 
                    startNode && endNode) {
                        if (curNode = replaceFn({
                            startNode: startNode,
                            startNodeIndex: startNodeIndex,
                            endNode: endNode,
                            endNodeIndex: endNodeIndex,
                            innerNodes: innerNodes,
                            match: matchLocation[2],
                            matchIndex: matchIndex
                        }), atIndex -= endNode.length - endNodeIndex, endNode = startNode = null, 
                        innerNodes = [], matchIndex++, !(matchLocation = matches.shift())) break;
                    } else {
                        if ((!hiddenTextElementsMap[curNode.nodeName] || blockElementsMap[curNode.nodeName]) && curNode.firstChild) {
                            curNode = curNode.firstChild;
                            continue;
                        }
                        if (curNode.nextSibling) {
                            curNode = curNode.nextSibling;
                            continue;
                        }
                    }
                    for (;;) {
                        if (curNode.nextSibling) {
                            curNode = curNode.nextSibling;
                            break;
                        }
                        if (curNode.parentNode === node) break out;
                        curNode = curNode.parentNode;
                    }
                }
            }(node, matches, (makeReplacementNode = "function" != typeof (schema = replacementNode) ? (stencilNode = schema.nodeType ? schema : doc.createElement(schema), 
            function(fill, matchIndex) {
                var clone = stencilNode.cloneNode(!1);
                return clone.setAttribute("data-mce-index", matchIndex), fill && clone.appendChild(doc.createTextNode(fill)), 
                clone;
            }) : schema, function(range) {
                var parentNode, startNode = range.startNode, endNode = range.endNode, matchIndex = range.matchIndex;
                if (startNode === endNode) return parentNode = (node = startNode).parentNode, 
                0 < range.startNodeIndex && (before = doc.createTextNode(node.data.substring(0, range.startNodeIndex)), 
                parentNode.insertBefore(before, node)), el = makeReplacementNode(range.match[0], matchIndex), 
                parentNode.insertBefore(el, node), range.endNodeIndex < node.length && (after = doc.createTextNode(node.data.substring(range.endNodeIndex)), 
                parentNode.insertBefore(after, node)), node.parentNode.removeChild(node), 
                el;
                for (var before = doc.createTextNode(startNode.data.substring(0, range.startNodeIndex)), after = doc.createTextNode(endNode.data.substring(range.endNodeIndex)), node = makeReplacementNode(startNode.data.substring(range.startNodeIndex), matchIndex), innerEls = [], i = 0, l = range.innerNodes.length; i < l; ++i) {
                    var innerNode = range.innerNodes[i], innerEl = makeReplacementNode(innerNode.data, matchIndex);
                    innerNode.parentNode.replaceChild(innerEl, innerNode), innerEls.push(innerEl);
                }
                var el = makeReplacementNode(endNode.data.substring(0, range.endNodeIndex), matchIndex);
                return (parentNode = startNode.parentNode).insertBefore(before, startNode), 
                parentNode.insertBefore(node, startNode), parentNode.removeChild(startNode), 
                (parentNode = endNode.parentNode).insertBefore(el, endNode), parentNode.insertBefore(after, endNode), 
                parentNode.removeChild(endNode), el;
            }))), count;
        }
    }
    tinymce.PluginManager.add("searchreplace", function(editor, url) {
        var last, self = this, currentIndex = -1;
        function notFoundAlert() {
            editor.windowManager.alert(editor.getLang("searchreplace.notfound", "The search has been completed. The search string could not be found."));
        }
        function updateButtonStates() {
            editor.updateSearchButtonStates.dispatch({
                next: !findSpansByIndex(currentIndex + 1).length,
                prev: !findSpansByIndex(currentIndex - 1).length
            });
        }
        function resetButtonStates() {
            editor.updateSearchButtonStates.dispatch({
                replace: !0,
                replaceAll: !0,
                next: !0,
                prev: !0
            });
        }
        function getElmIndex(elm) {
            elm = elm.getAttribute("data-mce-index");
            return "number" == typeof elm ? "" + elm : elm;
        }
        function unwrap(node) {
            var parentNode = node.parentNode;
            node.firstChild && parentNode.insertBefore(node.firstChild, node), node.parentNode.removeChild(node);
        }
        function findSpansByIndex(index) {
            var spans = [], nodes = tinymce.toArray(editor.getBody().getElementsByTagName("span"));
            if (nodes.length) for (var i = 0; i < nodes.length; i++) {
                var nodeIndex = getElmIndex(nodes[i]);
                null !== nodeIndex && nodeIndex.length && nodeIndex === index.toString() && spans.push(nodes[i]);
            }
            return spans;
        }
        function moveSelection(forward) {
            var testIndex = currentIndex, dom = editor.dom, forward = ((forward = !1 !== forward) ? testIndex++ : testIndex--, 
            dom.removeClass(findSpansByIndex(currentIndex), "mce-match-marker-selected"), 
            findSpansByIndex(testIndex));
            return forward.length ? (dom.addClass(findSpansByIndex(testIndex), "mce-match-marker-selected"), 
            editor.selection.scrollIntoView(forward[0]), testIndex) : -1;
        }
        function removeNode(node) {
            var dom = editor.dom, parent = node.parentNode;
            dom.remove(node), dom.isEmpty(parent) && dom.remove(parent);
        }
        function isMatchSpan(node) {
            node = getElmIndex(node);
            return null !== node && 0 < node.length;
        }
        function isMatchSpan(node) {
            node = getElmIndex(node);
            return null !== node && 0 < node.length;
        }
        editor.updateSearchButtonStates = new tinymce.util.Dispatcher(this), editor.addCommand("mceSearchReplace", function() {
            last = {};
            var html = '<div class="mceForm"><div class="mceModalRow">   <label for="' + editor.id + '_search_string">' + editor.getLang("searchreplace.findwhat", "Search") + '</label>   <div class="mceModalControl">       <input type="text" id="' + editor.id + '_search_string" />   </div>   <div class="mceModalControl mceModalFlexNone">       <button class="mceButton" id="' + editor.id + '_search_prev" title="' + editor.getLang("searchreplace.prev", "Previous") + '" disabled><i class="mceIcon mce_arrow-up"></i></button>       <button class="mceButton" id="' + editor.id + '_search_next" title="' + editor.getLang("searchreplace.next", "Next") + '" disabled><i class="mceIcon mce_arrow-down"></i></button>   </div></div><div class="mceModalRow">   <label for="' + editor.id + '_replace_string">' + editor.getLang("searchreplace.replacewith", "Replace") + '</label>   <div class="mceModalControl">       <input type="text" id="' + editor.id + '_replace_string" />   </div></div><div class="mceModalRow">   <div class="mceModalControl">       <input id="' + editor.id + '_matchcase" type="checkbox" />       <label for="' + editor.id + '_matchcase">' + editor.getLang("searchreplace.mcase", "Match Case") + '</label>   </div>   <div class="mceModalControl">       <input id="' + editor.id + '_wholewords" type="checkbox" />       <label for="' + editor.id + '_wholewords">' + editor.getLang("searchreplace.wholewords", "Whole Words") + "</label>   </div></div></div>";
            editor.windowManager.open({
                title: editor.getLang("searchreplace.search_desc", "Search and Replace"),
                content: html,
                size: "mce-modal-landscape-small",
                overlay: !1,
                open: function() {
                    var id = this.id, search = DOM.get(editor.id + "_search_string");
                    search.value = editor.selection.getContent({
                        format: "text"
                    }), DOM.bind(editor.id + "_search_next", "click", function(e) {
                        e.preventDefault(), editor.execCommand("mceSearchNext", !1);
                    }), DOM.bind(editor.id + "_search_prev", "click", function(e) {
                        e.preventDefault(), editor.execCommand("mceSearchPrev", !1);
                    }), window.setTimeout(function() {
                        search.focus();
                    }, 10), editor.updateSearchButtonStates.add(function(obj) {
                        tinymce.each(obj, function(val, key) {
                            key = DOM.get(editor.id + "_search_" + key) || DOM.get(id + "_search_" + key);
                            key && (key.disabled = !!val);
                        });
                    });
                },
                close: function() {
                    DOM.unbind(editor.id + "_search_next", "click"), DOM.unbind(editor.id + "_search_prev", "click"), 
                    editor.execCommand("mceSearchDone", !1);
                },
                buttons: [ {
                    title: editor.getLang("searchreplace.find", "Find"),
                    id: "find",
                    onclick: function(e) {
                        e.preventDefault();
                        var e = DOM.get(editor.id + "_matchcase"), wholeword = DOM.get(editor.id + "_wholewords"), text = DOM.getValue(editor.id + "_search_string");
                        editor.execCommand("mceSearch", !1, {
                            textcase: !!e.checked,
                            text: text,
                            wholeword: !!wholeword.checked
                        });
                    },
                    classes: "primary"
                }, {
                    title: editor.getLang("searchreplace.replace", "Replace"),
                    id: "search_replace",
                    onclick: function(e) {
                        e.preventDefault();
                        e = DOM.getValue(editor.id + "_replace_string");
                        editor.execCommand("mceReplace", !1, e);
                    }
                }, {
                    title: editor.getLang("searchreplace.replaceall", "Replace All"),
                    id: "search_replaceall",
                    onclick: function(e) {
                        e.preventDefault();
                        e = DOM.getValue(editor.id + "_replace_string");
                        editor.execCommand("mceReplaceAll", !1, e);
                    }
                } ]
            });
        }), editor.addCommand("mceSearch", function(ui, e) {
            var count, text = e.text, caseState = e.textcase, e = e.wholeword;
            if (text.length) {
                if (last.text == text && last.caseState == caseState && last.wholeWord == e) return 0 === findSpansByIndex(currentIndex + 1).length ? void notFoundAlert() : (self.next(), 
                void updateButtonStates());
                (count = self.find(text, caseState, e)) || notFoundAlert(), updateButtonStates(), 
                editor.updateSearchButtonStates.dispatch({
                    replace: !count,
                    replaceAll: !count
                }), last = {
                    text: text,
                    caseState: caseState,
                    wholeWord: e
                };
            } else self.done(!1), resetButtonStates();
        }), editor.addCommand("mceSearchNext", function() {
            self.next(), updateButtonStates();
        }), editor.addCommand("mceSearchPrev", function() {
            self.prev(), updateButtonStates();
        }), editor.addCommand("mceReplace", function(ui, text) {
            self.replace(text) || (resetButtonStates(), currentIndex = -1, last = {});
        }), editor.addCommand("mceReplaceAll", function(ui, text) {
            self.replace(text, !0, !0) || (resetButtonStates(), last = {});
        }), editor.addCommand("mceSearchDone", function() {
            self.done();
        }), editor.addButton("search", {
            title: "searchreplace.search_desc",
            cmd: "mceSearchReplace"
        }), editor.addShortcut("meta+f", "searchreplace.search_desc", function() {
            return editor.execCommand("mceSearchReplace");
        }), self.find = function(text, matchCase, wholeWord) {
            text = text.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 
            text = wholeWord ? "\\b" + text + "\\b" : text;
            wholeWord = new RegExp(text, matchCase ? "g" : "gi"), (text = editor.dom.create("span", {
                "data-mce-bogus": 1
            })).className = "mce-match-marker", matchCase = editor.getBody(), self.done(!1);
            wholeWord = findAndReplaceDOMText(wholeWord, matchCase, text, !1, editor.schema);
            return wholeWord && (currentIndex = -1, currentIndex = moveSelection(!0)), 
            wholeWord;
        }, self.next = function() {
            var index = moveSelection(!0);
            -1 !== index && (currentIndex = index);
        }, self.prev = function() {
            var index = moveSelection(!1);
            -1 !== index && (currentIndex = index);
        }, self.replace = function(text, forward, all) {
            var i, nodes, node, currentMatchIndex, hasMore, nextIndex = currentIndex;
            for (forward = !1 !== forward, node = editor.getBody(), nodes = tinymce.grep(tinymce.toArray(node.getElementsByTagName("span")), isMatchSpan), 
            nodes = tinymce.grep(nodes, function(node) {
                var parent = editor.dom.getParent(node, "[contenteditable]");
                return (!parent || "false" !== parent.contentEditable) && "false" !== node.contentEditable;
            }), i = 0; i < nodes.length; i++) {
                var nodeIndex = getElmIndex(nodes[i]), matchIndex = currentMatchIndex = parseInt(nodeIndex, 10);
                if (all || matchIndex === currentIndex) {
                    for ((text.length ? (nodes[i].firstChild.nodeValue = text, unwrap) : removeNode)(nodes[i]); nodes[++i]; ) {
                        if ((matchIndex = parseInt(getElmIndex(nodes[i]), 10)) !== currentMatchIndex) {
                            i--;
                            break;
                        }
                        removeNode(nodes[i]);
                    }
                    forward && nextIndex--;
                } else currentIndex < currentMatchIndex && nodes[i].setAttribute("data-mce-index", currentMatchIndex - 1);
            }
            return editor.undoManager.add(), currentIndex = nextIndex, forward ? (hasMore = 0 < findSpansByIndex(nextIndex + 1).length, 
            self.next()) : (hasMore = 0 < findSpansByIndex(nextIndex - 1).length, 
            self.prev()), !all && hasMore;
        }, self.done = function(keepEditorSelection) {
            var i, startContainer, endContainer, scrollBookmark, rng, nodes = tinymce.toArray(editor.getBody().getElementsByTagName("span"));
            if (!1 !== keepEditorSelection && 0 <= currentIndex) for (i = 0; i < nodes.length; i++) if (getElmIndex(nodes[i]) === currentIndex.toString()) {
                scrollBookmark = editor.dom.create("span", {
                    "data-mce-type": "bookmark",
                    "data-mce-bogus": "1"
                }), nodes[i].parentNode.insertBefore(scrollBookmark, nodes[i]);
                break;
            }
            for (i = 0; i < nodes.length; i++) {
                var nodeIndex = getElmIndex(nodes[i]);
                null !== nodeIndex && nodeIndex.length && (nodeIndex === currentIndex.toString() && (startContainer = startContainer || nodes[i].firstChild, 
                endContainer = nodes[i].firstChild), unwrap(nodes[i]));
            }
            if (startContainer && endContainer) return (rng = editor.dom.createRng()).setStart(startContainer, 0), 
            rng.setEnd(endContainer, endContainer.data.length), !1 !== keepEditorSelection && (editor.selection.setRng(rng), 
            scrollBookmark) && window.setTimeout(function() {
                editor.selection.scrollIntoView(scrollBookmark), editor.dom.remove(scrollBookmark);
            }, 0), rng;
            scrollBookmark && editor.dom.remove(scrollBookmark);
        };
    });
}();