/* jce - 2.8.14 | 2020-06-19 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2020 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
var ColorPicker={settings:{},init:function(){var ed=tinyMCEPopup.editor,color=tinyMCEPopup.getWindowArg("input_color")||"#FFFFFF",doc=ed.getDoc(),stylesheets=[];doc.styleSheets.length&&$.each(doc.styleSheets,function(i,s){s.href&&s.href.indexOf("tiny_mce")==-1&&stylesheets.push(s)}),$("#tmp_color").val(color).colorpicker($.extend(this.settings,{dialog:!0,stylesheets:stylesheets,custom_colors:ed.getParam("colorpicker_custom_colors"),labels:{name:ed.getLang("colorpicker.name","Name")}})).on("colorpicker:insert",function(){return ColorPicker.insert()}).on("colorpicker:close",function(){return tinyMCEPopup.close()}),$("button#insert").button({icons:{primary:"uk-icon-check"}}),$("#jce").css("display","block")},insert:function(){var color=$("#colorpicker_color").val(),f=tinyMCEPopup.getWindowArg("func");color&&(color="#"+color),tinyMCEPopup.restoreSelection(),f&&f(color),tinyMCEPopup.close()}};tinyMCEPopup.onInit.add(ColorPicker.init,ColorPicker);