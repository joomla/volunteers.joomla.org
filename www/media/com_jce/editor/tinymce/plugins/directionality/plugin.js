/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
tinymce.PluginManager.add("directionality", function(ed, url) {
    function setDir(dir) {
        var curDir, dom = ed.dom, blocks = ed.selection.getSelectedBlocks();
        blocks.length && (curDir = dom.getAttrib(blocks[0], "dir"), tinymce.each(blocks, function(block) {
            dom.getParent(block.parentNode, "*[dir='" + dir + "']", dom.getRoot()) || (curDir != dir ? dom.setAttrib(block, "dir", dir) : dom.setAttrib(block, "dir", null));
        }), ed.nodeChanged());
    }
    ed.addCommand("mceDirectionLTR", function() {
        setDir("ltr");
    }), ed.addCommand("mceDirectionRTL", function() {
        setDir("rtl");
    }), ed.addButton("ltr", {
        title: "directionality.ltr_desc",
        cmd: "mceDirectionLTR"
    }), ed.addButton("rtl", {
        title: "directionality.rtl_desc",
        cmd: "mceDirectionRTL"
    }), ed.onNodeChange.add(function(ed, cm, n) {
        var ed = ed.dom;
        (n = ed.getParent(n, ed.isBlock)) ? (ed = ed.getAttrib(n, "dir"), cm.setActive("ltr", "ltr" == ed), 
        cm.setDisabled("ltr", 0), cm.setActive("rtl", "rtl" == ed), cm.setDisabled("rtl", 0)) : (cm.setDisabled("ltr", 1), 
        cm.setDisabled("rtl", 1));
    });
});