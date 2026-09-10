/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($) {
    var ColorPicker = {
        settings: {},
        init: function() {
            var ed = tinyMCEPopup.editor, color = tinyMCEPopup.getWindowArg("input_color") || "#FFFFFF", doc = ed.getDoc(), stylesheets = [];
            doc.styleSheets.length && $.each(doc.styleSheets, function(i, s) {
                s.href && -1 == s.href.indexOf("tinymce") && stylesheets.push(s);
            }), $("#tmp_color").val(color).colorpicker($.extend(this.settings, {
                dialog: !0,
                stylesheets: stylesheets,
                custom_colors: ed.getParam("colorpicker_custom_colors"),
                labels: {
                    name: ed.getLang("colorpicker.name", "Name")
                }
            })).on("colorpicker:insert", function() {
                return ColorPicker.insert();
            }).on("colorpicker:close", function() {
                return tinyMCEPopup.close();
            }), $("button#insert").button({
                icons: {
                    primary: "uk-icon-check"
                }
            }), $("#jce").css("display", "block");
        },
        insert: function() {
            var color = $("#colorpicker_color").val(), f = tinyMCEPopup.getWindowArg("func"), color = color && "#" + color;
            tinyMCEPopup.restoreSelection(), f && f(color), tinyMCEPopup.close();
        }
    };
    tinyMCEPopup.onInit.add(ColorPicker.init, ColorPicker);
}(jQuery);