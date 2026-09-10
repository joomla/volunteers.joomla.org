/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
tinymce.PluginManager.add("help", function(ed, url) {
    ed.addCommand("mceHelp", function() {
        ed.windowManager.open({
            title: ed.getLang("dlg.help", "Help"),
            url: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=help&lang=" + ed.getParam("language") + "&section=editor&category=editor&article=about",
            size: "mce-modal-landscape-full"
        });
    }), ed.addButton("help", {
        title: "dlg.help",
        cmd: "mceHelp"
    });
});