/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    "use strict";
    function isBr(node) {
        return node && "BR" === node.nodeName;
    }
    function getNormalizedEndPoint(container, offset) {
        var node = RangeUtils$1.getNode(container, offset);
        return NodeType.isListItemNode(container) && NodeType.isTextNode(node) ? {
            container: node,
            offset: offset >= container.childNodes.length ? node.data.length : 0
        } : {
            container: container,
            offset: offset
        };
    }
    function getParentList(editor) {
        return editor.dom.getParent(editor.selection.getStart(!0), "OL,UL,DL");
    }
    function normalizeList(dom, ul) {
        var sibling, parentNode = ul.parentNode;
        "LI" === parentNode.nodeName && parentNode.firstChild === ul && ((sibling = parentNode.previousSibling) && "LI" === sibling.nodeName ? (sibling.appendChild(ul), 
        NodeType.isEmpty(dom, parentNode) && DOM$3.remove(parentNode)) : DOM$3.setStyle(parentNode, "listStyleType", "none")), 
        NodeType.isListNode(parentNode) && (sibling = parentNode.previousSibling) && "LI" === sibling.nodeName && sibling.appendChild(ul);
    }
    function removeEmptyLi(dom, li) {
        NodeType.isEmpty(dom, li) && DOM.remove(li);
    }
    function outdent(editor, li) {
        var ul = li.parentNode, ulParent = ul.parentNode;
        return ul !== editor.getBody() && ("DD" === li.nodeName ? DOM.rename(li, "DT") : NodeType.isFirstChild(li) && NodeType.isLastChild(li) ? "LI" === ulParent.nodeName ? (DOM.insertAfter(li, ulParent), 
        removeEmptyLi(editor.dom, ulParent), DOM.remove(ul)) : NodeType.isListNode(ulParent) ? DOM.remove(ul, !0) : (ulParent.insertBefore(TextBlock_createNewTextBlock(editor, li), ul), 
        DOM.remove(ul)) : NodeType.isFirstChild(li) ? "LI" === ulParent.nodeName ? (DOM.insertAfter(li, ulParent), 
        li.appendChild(ul), removeEmptyLi(editor.dom, ulParent)) : NodeType.isListNode(ulParent) ? ulParent.insertBefore(li, ul) : (ulParent.insertBefore(TextBlock_createNewTextBlock(editor, li), ul), 
        DOM.remove(li)) : NodeType.isLastChild(li) ? "LI" === ulParent.nodeName ? DOM.insertAfter(li, ulParent) : NodeType.isListNode(ulParent) ? DOM.insertAfter(li, ul) : (DOM.insertAfter(TextBlock_createNewTextBlock(editor, li), ul), 
        DOM.remove(li)) : (ulParent = "LI" === ulParent.nodeName ? (ul = ulParent, 
        TextBlock_createNewTextBlock(editor, li, "LI")) : NodeType.isListNode(ulParent) ? TextBlock_createNewTextBlock(editor, li, "LI") : TextBlock_createNewTextBlock(editor, li), 
        SplitList_splitList(editor, ul, li, ulParent), NormalizeLists_normalizeLists(editor.dom, ul.parentNode))), 
        !0;
    }
    function setAttribs(elm, attrs) {
        tinymce.each(attrs, function(value, key) {
            elm.setAttribute(key, value);
        });
    }
    function getEndPointNode(editor, rng, start) {
        var root = getRoot(editor), container = rng[start ? "startContainer" : "endContainer"], rng = rng[start ? "startOffset" : "endOffset"];
        for (1 === container.nodeType && (container = container.childNodes[Math.min(rng, container.childNodes.length - 1)] || container); container.parentNode !== root; ) {
            if (NodeType.isTextBlock(editor, container)) return container;
            if (/^(TD|TH)$/.test(container.parentNode.nodeName)) return container;
            container = container.parentNode;
        }
        return container;
    }
    function shouldMerge(dom, list1, list2) {
        return isValidLists(list1, list2) && hasSameListStyle(dom, list1, list2) && hasSameClasses(list1, list2);
    }
    function findNextCaretContainer(editor, rng, isForward) {
        var nonEmptyBlocks, walker, node = rng.startContainer, rng = rng.startOffset;
        if (3 === node.nodeType && (isForward ? rng < node.data.length : 0 < rng)) return node;
        for (nonEmptyBlocks = editor.schema.getNonEmptyElements(), 1 === node.nodeType && (node = RangeUtils.getNode(node, rng)), 
        walker = new TreeWalker(node, editor.getBody()), isForward && NodeType.isBogusBr(editor.dom, node) && walker.next(); node = walker[isForward ? "next" : "prev2"](); ) {
            if ("LI" === node.nodeName && !node.hasChildNodes()) return node;
            if (nonEmptyBlocks[node.nodeName]) return node;
            if (3 === node.nodeType && 0 < node.data.length) return node;
        }
    }
    function hasOnlyOneBlockChild(dom, elm) {
        return 1 === (elm = elm.childNodes).length && !NodeType.isListNode(elm[0]) && dom.isBlock(elm[0]);
    }
    function moveChildren(dom, fromElm, toElm) {
        var node, targetElm = hasOnlyOneBlockChild(dom, toElm) ? toElm.firstChild : toElm;
        if (!function(dom, elm) {
            hasOnlyOneBlockChild(dom, elm) && dom.remove(elm.firstChild, !0);
        }(dom, fromElm), !NodeType.isEmpty(dom, fromElm, !0)) for (;node = fromElm.firstChild; ) targetElm.appendChild(node);
    }
    function mergeLiElements(dom, fromElm, toElm) {
        var node, listNode, ul = fromElm.parentNode;
        NodeType.isChildOfBody(dom, fromElm) && NodeType.isChildOfBody(dom, toElm) && (NodeType.isListNode(toElm.lastChild) && (listNode = toElm.lastChild), 
        ul === toElm.lastChild && NodeType.isBr(ul.previousSibling) && dom.remove(ul.previousSibling), 
        (node = toElm.lastChild) && NodeType.isBr(node) && fromElm.hasChildNodes() && dom.remove(node), 
        NodeType.isEmpty(dom, toElm, !0) && dom.empty(toElm), moveChildren(dom, fromElm, toElm), 
        listNode && toElm.appendChild(listNode), dom.remove(fromElm), NodeType.isEmpty(dom, ul)) && ul !== dom.getRoot() && dom.remove(ul);
    }
    function mergeForward(editor, rng, fromLi, toLi) {
        var dom = editor.dom;
        dom.isEmpty(toLi) ? function(editor, fromLi, toLi) {
            editor.dom.empty(toLi), mergeLiElements(editor.dom, fromLi, toLi), editor.selection.setCursorLocation(toLi);
        }(editor, fromLi, toLi) : (rng = Bookmark.createBookmark(rng), mergeLiElements(dom, fromLi, toLi), 
        editor.selection.setRng(Bookmark.resolveBookmark(rng)));
    }
    function backspaceDeleteFromListToListCaret(editor, isForward) {
        var ul, dom = editor.dom, selection = editor.selection, li = dom.getParent(selection.getStart(), "LI");
        if (li) {
            if ((ul = li.parentNode) === editor.getBody() && NodeType.isEmpty(dom, ul)) return !0;
            if (selection = Range_normalizeRange(selection.getRng(!0)), (dom = dom.getParent(findNextCaretContainer(editor, selection, isForward), "LI")) && dom !== li) return isForward ? mergeForward(editor, selection, dom, li) : function(editor, rng, fromLi, toLi) {
                rng = Bookmark.createBookmark(rng);
                mergeLiElements(editor.dom, fromLi, toLi), editor.selection.setRng(Bookmark.resolveBookmark(rng));
            }(editor, selection, li, dom), !0;
            if (!dom && !isForward && ToggleList_removeList(editor, ul.nodeName)) return !0;
        }
        return !1;
    }
    function backspaceDelete(editor, isForward) {
        return editor.selection.isCollapsed() ? backspaceDeleteCaret(editor, isForward) : backspaceDeleteRange(editor);
    }
    function queryListCommandState(editor, listName) {
        return function() {
            var parentList = editor.dom.getParent(editor.selection.getStart(), "UL,OL,DL");
            return parentList && parentList.nodeName === listName;
        };
    }
    var NodeType = {
        isTextNode: function(node) {
            return node && 3 === node.nodeType;
        },
        isListNode: function(node) {
            return node && /^(OL|UL|DL)$/.test(node.nodeName);
        },
        isListItemNode: function(node) {
            return node && /^(LI|DT|DD)$/.test(node.nodeName);
        },
        isBr: isBr,
        isFirstChild: function(node) {
            return node.parentNode.firstChild === node;
        },
        isLastChild: function(node) {
            return node.parentNode.lastChild === node;
        },
        isTextBlock: function(editor, node) {
            return node && !!editor.schema.getTextBlockElements()[node.nodeName];
        },
        isBlock: function(node, blockElements) {
            return node && node.nodeName in blockElements;
        },
        isBogusBr: function(dom, node) {
            return !!isBr(node) && !(!dom.isBlock(node.nextSibling) || isBr(node.previousSibling));
        },
        isEmpty: function(dom, elm, keepBookmarks) {
            var empty = dom.isEmpty(elm);
            return !(keepBookmarks && 0 < dom.select("span[data-mce-type=bookmark]", elm).length) && empty;
        },
        isChildOfBody: function(dom, elm) {
            return dom.isChildOf(elm, dom.getRoot());
        }
    }, RangeUtils$1 = tinymce.dom.RangeUtils, Range_normalizeRange = function(rng) {
        var outRng = rng.cloneRange(), rangeStart = getNormalizedEndPoint(rng.startContainer, rng.startOffset), rangeStart = (outRng.setStart(rangeStart.container, rangeStart.offset), 
        getNormalizedEndPoint(rng.endContainer, rng.endOffset));
        return outRng.setEnd(rangeStart.container, rangeStart.offset), outRng;
    }, DOM$5 = tinymce.DOM, Bookmark = {
        createBookmark: function(rng) {
            function setupEndPoint(start) {
                var offsetNode, container = rng[start ? "startContainer" : "endContainer"], offset = rng[start ? "startOffset" : "endOffset"];
                1 === container.nodeType && (offsetNode = DOM$5.create("span", {
                    "data-mce-type": "bookmark"
                }), container.hasChildNodes() ? (offset = Math.min(offset, container.childNodes.length - 1), 
                start ? container.insertBefore(offsetNode, container.childNodes[offset]) : DOM$5.insertAfter(offsetNode, container.childNodes[offset])) : container.appendChild(offsetNode), 
                container = offsetNode, offset = 0), bookmark[start ? "startContainer" : "endContainer"] = container, 
                bookmark[start ? "startOffset" : "endOffset"] = offset;
            }
            var bookmark = {};
            return setupEndPoint(!0), rng.collapsed || setupEndPoint(), bookmark;
        },
        resolveBookmark: function(bookmark) {
            function restoreEndPoint(start) {
                var node, container = node = bookmark[start ? "startContainer" : "endContainer"], offset = bookmark[start ? "startOffset" : "endOffset"];
                container && (1 === container.nodeType && (offset = function(container) {
                    for (var node = container.parentNode.firstChild, idx = 0; node; ) {
                        if (node === container) return idx;
                        1 === node.nodeType && "bookmark" === node.getAttribute("data-mce-type") || idx++, 
                        node = node.nextSibling;
                    }
                    return -1;
                }(container), container = container.parentNode, DOM$5.remove(node)), 
                bookmark[start ? "startContainer" : "endContainer"] = container, 
                bookmark[start ? "startOffset" : "endOffset"] = offset);
            }
            restoreEndPoint(!0), restoreEndPoint();
            var rng = DOM$5.createRng();
            return rng.setStart(bookmark.startContainer, bookmark.startOffset), 
            bookmark.endContainer && rng.setEnd(bookmark.endContainer, bookmark.endOffset), 
            Range_normalizeRange(rng);
        }
    }, Selection_getParentList = getParentList, Selection_getSelectedSubLists = function(editor) {
        var parentList = getParentList(editor);
        return tinymce.grep(editor.selection.getSelectedBlocks(), function(elm) {
            return NodeType.isListNode(elm) && parentList !== elm;
        });
    }, Selection_getSelectedListItems = function(editor) {
        var selectedBlocks = editor.selection.getSelectedBlocks();
        return tinymce.grep(function(editor, elms) {
            elms = tinymce.map(elms, function(elm) {
                var parentLi = editor.dom.getParent(elm, "li,dd,dt", editor.getBody());
                return parentLi || elm;
            });
            return editor.dom.unique(elms);
        }(editor, selectedBlocks), function(block) {
            return NodeType.isListItemNode(block);
        });
    }, DOM$4 = tinymce.DOM, mergeLists = function(from, to) {
        var node;
        if (NodeType.isListNode(from)) {
            for (;node = from.firstChild; ) to.appendChild(node);
            DOM$4.remove(from);
        }
    }, Indent_indentSelection = function(editor) {
        var li, sibling, newList, listStyle, listElements = Selection_getSelectedListItems(editor);
        if (listElements.length) {
            for (var bookmark = Bookmark.createBookmark(editor.selection.getRng(!0)), i = 0; i < listElements.length && (li = listElements[i], 
            listStyle = newList = sibling = void 0, ("DT" === li.nodeName ? (DOM$4.rename(li, "DD"), 
            !0) : (sibling = li.previousSibling) && NodeType.isListNode(sibling) ? (sibling.appendChild(li), 
            !0) : sibling && "LI" === sibling.nodeName && NodeType.isListNode(sibling.lastChild) ? (sibling.lastChild.appendChild(li), 
            mergeLists(li.lastChild, sibling.lastChild), !0) : (sibling = li.nextSibling) && NodeType.isListNode(sibling) ? (sibling.insertBefore(li, sibling.firstChild), 
            !0) : !(!(sibling = li.previousSibling) || "LI" !== sibling.nodeName || (newList = DOM$4.create(li.parentNode.nodeName), 
            (listStyle = DOM$4.getStyle(li.parentNode, "listStyleType")) && DOM$4.setStyle(newList, "listStyleType", listStyle), 
            sibling.appendChild(newList), newList.appendChild(li), mergeLists(li.lastChild, newList), 
            0))) || 0 !== i); i++);
            return editor.selection.setRng(Bookmark.resolveBookmark(bookmark)), 
            editor.nodeChanged(), !0;
        }
    }, DOM$3 = tinymce.DOM, NormalizeLists_normalizeLists = function(dom, element) {
        tinymce.each(tinymce.grep(dom.select("ol,ul", element)), function(ul) {
            normalizeList(dom, ul);
        });
    }, DOM$2 = tinymce.DOM, TextBlock_createNewTextBlock = function(editor, contentNode, blockName) {
        var node, textBlock, hasContentNode, fragment = DOM$2.createFragment(), blockElements = editor.schema.getBlockElements();
        if ((blockName = editor.settings.forced_root_block ? blockName || editor.settings.forced_root_block : blockName) && ((textBlock = DOM$2.create(blockName)).tagName === editor.settings.forced_root_block && DOM$2.setAttribs(textBlock, editor.settings.forced_root_block_attrs), 
        NodeType.isBlock(contentNode.firstChild, blockElements) || fragment.appendChild(textBlock)), 
        contentNode) for (;node = contentNode.firstChild; ) {
            var nodeName = node.nodeName;
            hasContentNode || "SPAN" === nodeName && "bookmark" === node.getAttribute("data-mce-type") || (hasContentNode = !0), 
            NodeType.isBlock(node, blockElements) ? (fragment.appendChild(node), 
            textBlock = null) : (blockName ? (textBlock || (textBlock = DOM$2.create(blockName), 
            fragment.appendChild(textBlock)), textBlock) : fragment).appendChild(node);
        }
        return editor.settings.forced_root_block ? hasContentNode || textBlock.appendChild(DOM$2.create("br", {
            "data-mce-bogus": "1"
        })) : fragment.appendChild(DOM$2.create("br")), fragment;
    }, DOM$1 = tinymce.DOM, SplitList_splitList = function(editor, ul, li, newBlock) {
        var tmpRng, node, targetNode, bookmarks = DOM$1.select('span[data-mce-type="bookmark"]', ul);
        for (newBlock = newBlock || TextBlock_createNewTextBlock(editor, li), (tmpRng = DOM$1.createRng()).setStartAfter(li), 
        tmpRng.setEndAfter(ul), node = (tmpRng = tmpRng.extractContents()).firstChild; node; node = node.firstChild) if ("LI" === node.nodeName && editor.dom.isEmpty(node)) {
            DOM$1.remove(node);
            break;
        }
        editor.dom.isEmpty(tmpRng) || DOM$1.insertAfter(tmpRng, ul), DOM$1.insertAfter(newBlock, ul), 
        NodeType.isEmpty(editor.dom, li.parentNode) && (targetNode = li.parentNode, 
        tinymce.each(bookmarks, function(node) {
            targetNode.parentNode.insertBefore(node, li.parentNode);
        }), DOM$1.remove(targetNode)), DOM$1.remove(li), NodeType.isEmpty(editor.dom, ul) && DOM$1.remove(ul);
    }, DOM = tinymce.DOM, Outdent_outdent = outdent, Outdent_outdentSelection = function(editor) {
        var listElements = Selection_getSelectedListItems(editor);
        if (listElements.length) {
            for (var y, bookmark = Bookmark.createBookmark(editor.selection.getRng(!0)), root = editor.dom.getRoot(), i = listElements.length; i--; ) for (var node = listElements[i].parentNode; node && node !== root; ) {
                for (y = listElements.length; y--; ) if (listElements[y] === node) {
                    listElements.splice(i, 1);
                    break;
                }
                node = node.parentNode;
            }
            for (i = 0; i < listElements.length && (outdent(editor, listElements[i]) || 0 !== i); i++);
            return editor.selection.setRng(Bookmark.resolveBookmark(bookmark)), 
            editor.nodeChanged(), !0;
        }
    }, BookmarkManager = tinymce.dom.BookmarkManager, getRoot = function(editor) {
        return editor.dom.getRoot();
    }, updateListWithDetails = function(dom, el, detail) {
        !function(dom, el, detail) {
            detail = detail["list-style-type"] || null;
            dom.setStyle(el, "list-style-type", detail);
        }(dom, el, detail), function(dom, el, detail) {
            setAttribs(el, detail["list-attributes"]), tinymce.each(dom.select("li", el), function(li) {
                setAttribs(li, detail["list-item-attributes"]);
            });
        }(dom, el, detail);
    }, applyList = function(editor, listName, detail) {
        var bookmark, rng = editor.selection.getRng(!0), listItemName = "LI", dom = editor.dom;
        detail = detail || {}, "false" !== dom.getContentEditable(editor.selection.getNode()) && ("DL" === (listName = listName.toUpperCase()) && (listItemName = "DT"), 
        bookmark = Bookmark.createBookmark(rng), tinymce.each(function(editor, rng) {
            for (var block, textBlocks = [], root = getRoot(editor), dom = editor.dom, startNode = getEndPointNode(editor, rng, !0), endNode = getEndPointNode(editor, rng, !1), siblings = [], node = startNode; node && (siblings.push(node), 
            node !== endNode); node = node.nextSibling);
            return tinymce.each(siblings, function(node) {
                var nextSibling;
                NodeType.isTextBlock(editor, node) ? (textBlocks.push(node), block = null) : dom.isBlock(node) || NodeType.isBr(node) ? (NodeType.isBr(node) && dom.remove(node), 
                block = null) : (nextSibling = node.nextSibling, BookmarkManager.isBookmarkNode(node) && (NodeType.isTextBlock(editor, nextSibling) || !nextSibling && node.parentNode === root) ? block = null : (block || (block = dom.create("p"), 
                node.parentNode.insertBefore(block, node), textBlocks.push(block)), 
                block.appendChild(node)));
            }), textBlocks;
        }(editor, rng), function(block, idx) {
            var listBlock, detailStyle, sibling = block.previousSibling;
            "DL" === listName && 0 < idx && (listItemName = "DD"), sibling && NodeType.isListNode(sibling) && sibling.nodeName === listName && (idx = sibling, 
            idx = dom.getStyle(idx, "list-style-type"), detailStyle = detail ? detail["list-style-type"] : "", 
            idx === (null === detailStyle ? "" : detailStyle)) ? (listBlock = sibling, 
            block = dom.rename(block, listItemName), sibling.appendChild(block)) : (listBlock = dom.create(listName), 
            block.parentNode.insertBefore(listBlock, block), listBlock.appendChild(block), 
            block = dom.rename(block, listItemName)), updateListWithDetails(dom, listBlock, detail), 
            mergeWithAdjacentLists(editor.dom, listBlock);
        }), editor.selection.setRng(Bookmark.resolveBookmark(bookmark)));
    }, removeList = function(editor) {
        var bookmark = Bookmark.createBookmark(editor.selection.getRng(!0)), root = getRoot(editor), listItems = Selection_getSelectedListItems(editor), emptyListItems = tinymce.grep(listItems, function(li) {
            return editor.dom.isEmpty(li);
        }), listItems = tinymce.grep(listItems, function(li) {
            return !editor.dom.isEmpty(li);
        });
        tinymce.each(emptyListItems, function(li) {
            NodeType.isEmpty(editor.dom, li) && Outdent_outdent(editor, li);
        }), tinymce.each(listItems, function(li) {
            var node, rootList;
            if (li.parentNode !== editor.getBody()) {
                for (node = li; node && node !== root; node = node.parentNode) NodeType.isListNode(node) && (rootList = node);
                SplitList_splitList(editor, rootList, li), NormalizeLists_normalizeLists(editor.dom, rootList.parentNode);
            }
        }), editor.selection.setRng(Bookmark.resolveBookmark(bookmark));
    }, isValidLists = function(list1, list2) {
        return list1 && list2 && NodeType.isListNode(list1) && list1.nodeName === list2.nodeName;
    }, hasSameListStyle = function(dom, list1, list2) {
        return dom.getStyle(list1, "list-style-type", !0) === dom.getStyle(list2, "list-style-type", !0);
    }, hasSameClasses = function(elm1, elm2) {
        return elm1.className === elm2.className;
    }, mergeWithAdjacentLists = function(dom, listBlock) {
        var node, sibling = listBlock.nextSibling;
        if (shouldMerge(dom, listBlock, sibling)) {
            for (;node = sibling.firstChild; ) listBlock.appendChild(node);
            dom.remove(sibling);
        }
        if (sibling = listBlock.previousSibling, shouldMerge(dom, listBlock, sibling)) {
            for (;node = sibling.lastChild; ) listBlock.insertBefore(node, listBlock.firstChild);
            dom.remove(sibling);
        }
    }, updateList = function(dom, list, listName, detail) {
        list.nodeName !== listName ? (listName = dom.rename(list, listName), updateListWithDetails(dom, listName, detail)) : updateListWithDetails(dom, list, detail);
    }, hasListStyleDetail = function(detail) {
        return "list-style-type" in detail;
    }, ToggleList_toggleList = function(editor, listName, detail) {
        var parentList = Selection_getParentList(editor), selectedSubLists = Selection_getSelectedSubLists(editor);
        detail = detail || {}, parentList && 0 < selectedSubLists.length ? function(editor, parentList, lists, listName, detail) {
            var bookmark;
            parentList.nodeName !== listName || hasListStyleDetail(detail) ? (bookmark = Bookmark.createBookmark(editor.selection.getRng(!0)), 
            tinymce.each([ parentList ].concat(lists), function(elm) {
                updateList(editor.dom, elm, listName, detail);
            }), editor.selection.setRng(Bookmark.resolveBookmark(bookmark))) : removeList(editor);
        }(editor, parentList, selectedSubLists, listName, detail) : function(editor, parentList, listName, detail) {
            var bookmark;
            parentList !== getRoot(editor) && (parentList ? parentList.nodeName !== listName || hasListStyleDetail(detail) ? (bookmark = Bookmark.createBookmark(editor.selection.getRng(!0)), 
            updateListWithDetails(editor.dom, parentList, detail), mergeWithAdjacentLists(editor.dom, editor.dom.rename(parentList, listName)), 
            editor.selection.setRng(Bookmark.resolveBookmark(bookmark))) : removeList(editor) : applyList(editor, listName, detail));
        }(editor, parentList, listName, detail);
    }, ToggleList_removeList = removeList, ToggleList_mergeWithAdjacentLists = mergeWithAdjacentLists, RangeUtils = tinymce.dom.RangeUtils, TreeWalker = tinymce.dom.TreeWalker, VK$1 = tinymce.VK, backspaceDeleteCaret = function(editor, isForward) {
        return backspaceDeleteFromListToListCaret(editor, isForward) || function(editor, isForward) {
            var dom = editor.dom, block = dom.getParent(editor.selection.getStart(), dom.isBlock);
            if (block && dom.isEmpty(block)) {
                var rng = Range_normalizeRange(editor.selection.getRng(!0)), rng = dom.getParent(findNextCaretContainer(editor, rng, isForward), "LI");
                if (rng) return editor.undoManager.add(), function(dom, block) {
                    var parentBlock = dom.getParent(block.parentNode, dom.isBlock);
                    dom.remove(block), parentBlock && dom.isEmpty(parentBlock) && dom.remove(parentBlock);
                }(dom, block), ToggleList_mergeWithAdjacentLists(dom, rng.parentNode), 
                editor.selection.select(rng, !0), editor.selection.collapse(isForward), 
                !0;
            }
            return !1;
        }(editor, isForward);
    }, backspaceDeleteRange = function(editor) {
        return !!(editor.dom.getParent(editor.selection.getStart(), "LI,DT,DD") || 0 < Selection_getSelectedListItems(editor).length) && (editor.undoManager.add(), 
        editor.execCommand("Delete"), NormalizeLists_normalizeLists(editor.dom, editor.getBody()), 
        !0);
    }, Delete_setup = function(editor) {
        editor.onKeyDown.add(function(ed, e) {
            e.keyCode === VK$1.BACKSPACE ? backspaceDelete(editor, !1) && e.preventDefault() : e.keyCode === VK$1.DELETE && backspaceDelete(editor, !0) && e.preventDefault();
        });
    }, Delete_backspaceDelete = backspaceDelete, VK = tinymce.VK;
    tinymce.PluginManager.add("lists", function(editor) {
        Delete_setup(editor), editor.onInit.add(function() {
            !function(editor) {
                editor.onBeforeExecCommand.add(function(ed, cmd, ui, v, o) {
                    var isHandled;
                    if ("indent" === cmd ? Indent_indentSelection(editor) && (isHandled = !0) : "outdent" === cmd && Outdent_outdentSelection(editor) && (isHandled = !0), 
                    isHandled) return editor.execCommand(cmd), o.terminate = !0;
                }), editor.addCommand("InsertUnorderedList", function(ui, detail) {
                    ToggleList_toggleList(editor, "UL", detail);
                }), editor.addCommand("InsertOrderedList", function(ui, detail) {
                    ToggleList_toggleList(editor, "OL", detail);
                }), editor.addCommand("InsertDefinitionList", function(ui, detail) {
                    ToggleList_toggleList(editor, "DL", detail);
                });
            }(editor), function(editor) {
                editor.addQueryStateHandler("InsertUnorderedList", queryListCommandState(editor, "UL")), 
                editor.addQueryStateHandler("InsertOrderedList", queryListCommandState(editor, "OL")), 
                editor.addQueryStateHandler("InsertDefinitionList", queryListCommandState(editor, "DL"));
            }(editor), editor.getParam("lists_indent_on_tab", !0) && function(editor) {
                editor.onKeyDown.add(function(ed, e) {
                    9 !== e.keyCode || VK.metaKeyPressed(e) || editor.dom.getParent(editor.selection.getStart(), "LI,DT,DD") && (e.preventDefault(), 
                    (e.shiftKey ? Outdent_outdentSelection : Indent_indentSelection)(editor));
                });
            }(editor);
        });
        var iconMap = {
            numlist: "OL",
            bullist: "UL"
        };
        editor.onNodeChange.add(function(ed, cm, n, collapsed, args) {
            var lists = tinymce.grep(args.parents, NodeType.isListNode);
            tinymce.each(iconMap, function(listName, btnName) {
                cm.setActive(btnName, 0 < lists.length && lists[0].nodeName === listName);
            });
        }), this.backspaceDelete = function(isForward) {
            return Delete_backspaceDelete(editor, isForward);
        };
    });
}();