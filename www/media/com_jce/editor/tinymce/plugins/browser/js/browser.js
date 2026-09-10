/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($) {
    function insert() {
        var win = tinyMCEPopup.getWindowArg("window"), callback = tinyMCEPopup.getWindowArg("callback");
        if (!callback) return tinyMCEPopup.close();
        $("[data-filebrowser]").trigger("filebrowser:insert", function(selected, data) {
            data.length || (data = [ {
                title: "",
                url: ""
            } ]), "string" == typeof callback && (selectFile(data[0]), win.document.getElementById(callback).value = $("[data-filebrowser]").val()), 
            "function" == typeof callback && callback(selected, data), tinyMCEPopup.close();
        });
    }
    function selectFile(file) {
        file = (file = file.url || "").replace(/^\//, "");
        $("[data-filebrowser]").val(file);
    }
    $(document).ready(function() {
        $("#insert").on("click", function(e) {
            e.preventDefault(), insert();
        }), $("#cancel").on("click", function(e) {
            e.preventDefault(), tinyMCEPopup.close();
        });
        var ed = tinyMCEPopup.editor, src = tinyMCEPopup.getWindowArg("value");
        Wf.init(), src && -1 != src.indexOf("#joomlaImage") && (src = src.substring(0, src.indexOf("#"))), 
        (src = /(:\/\/|www|index.php(.*)\?option)/gi.test(src) ? "" : src) && (src = ed.convertURL(src), 
        $(".uk-button-text", "#insert").text(tinyMCEPopup.getLang("update", "Update", !0))), 
        $("[data-filebrowser]").val(src).filebrowser().on("filebrowser:onfileclick", function(e, file, data) {
            selectFile(data);
        }).on("filebrowser:onfileinsert", function(e, file, data) {
            insert();
        });
    });
}(jQuery);