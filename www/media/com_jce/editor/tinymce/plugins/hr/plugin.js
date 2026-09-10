/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var VK = tinymce.VK, BACKSPACE = VK.BACKSPACE, DELETE = VK.DELETE;
    tinymce.PluginManager.add("hr", function(ed, url) {
        function isHR(n) {
            return "HR" === n.nodeName && !1 === /mce-item-(pagebreak|readmore)/.test(n.className);
        }
        ed.addCommand("InsertHorizontalRule", function(ui, v) {
            var hr, el, p, n = ed.selection.getNode();
            /^(H[1-6]|P)$/.test(n.nodeName) ? (ed.undoManager.add(), ed.execCommand("mceInsertContent", !1, '<span id="mce_hr_marker" data-mce-type="bookmark">\ufeff</span>', {
                skip_undo: 1
            }), n = ed.dom.get("mce_hr_marker"), hr = ed.dom.create("hr"), p = ed.dom.getParent(n, "H1,H2,H3,H4,H5,H6,P,DIV,ADDRESS,PRE,FORM,TABLE,OL,UL,CAPTION,BLOCKQUOTE,CENTER,DL,DIR,FIELDSET,NOSCRIPT,NOFRAMES,MENU,ISINDEX,SAMP,SECTION,ARTICLE,HGROUP,ASIDE,FIGURE", "BODY"), 
            ed.dom.split(p, n), (p = n.nextSibling) || (el = ed.getParam("forced_root_block") || "br", 
            p = ed.dom.create(el), "br" != el && (p.innerHTML = "\xa0"), ed.dom.insertAfter(p, n)), 
            p && ed.selection.setCursorLocation(p, p.childNodes.length), ed.dom.replace(hr, n)) : ed.execCommand("mceInsertContent", !1, "<hr />"), 
            ed.undoManager.add();
        }), ed.addButton("hr", {
            title: "advanced.hr_desc",
            cmd: "InsertHorizontalRule"
        }), ed.onNodeChange.add(function(ed, cm, n) {
            var s = isHR(n);
            cm.setActive("hr", s), ed.dom.removeClass(ed.dom.select("hr.mce-item-selected:not(.mce-item-pagebreak,.mce-item-readmore)"), "mce-item-selected"), 
            s && ed.dom.addClass(n, "mce-item-selected");
        }), ed.onKeyDown.add(function(ed, e) {
            var n, sib;
            e.keyCode != BACKSPACE && e.keyCode != DELETE || isHR(n = ed.selection.getNode()) && (sib = n.previousSibling, 
            ed.dom.remove(n), e.preventDefault(), sib = ed.dom.isBlock(sib) || (sib = n.nextSibling, 
            ed.dom.isBlock(sib)) ? sib : null) && ed.selection.setCursorLocation(sib, sib.childNodes.length);
        });
    });
}();