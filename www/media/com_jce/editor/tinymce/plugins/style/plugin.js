/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
tinymce.PluginManager.add("style", function(ed, url) {
    function isRootNode(node) {
        return node == ed.dom.getRoot();
    }
    window.matchMedia("(max-width: 600px)").matches || (ed.addCommand("mceStyleProps", function() {
        var applyStyleToBlocks = !1, blocks = ed.selection.getSelectedBlocks(), styles = [];
        1 === blocks.length ? styles.push(ed.selection.getNode().style.cssText) : (tinymce.each(blocks, function(block) {
            styles.push(ed.dom.getAttrib(block, "style"));
        }), applyStyleToBlocks = !0), ed.windowManager.open({
            file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=style",
            size: "mce-modal-landscape-xxlarge"
        }, {
            applyStyleToBlocks: applyStyleToBlocks,
            plugin_url: url,
            styles: styles
        });
    }), ed.addCommand("mceSetElementStyle", function(ui, v) {
        var node = ed.selection.getNode();
        node && (ed.dom.setAttrib(node, "style", v), ed.execCommand("mceRepaint"));
    }), ed.onNodeChange.add(function(ed, cm, n) {
        cm.setDisabled("style", isRootNode(n) || n.hasAttribute("data-mce-bogus"));
    }), ed.addButton("style", {
        title: "style.desc",
        cmd: "mceStyleProps"
    }));
});