/* jce - 2.9.5 | 2021-04-07 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){tinymce.create("tinymce.plugins.Print",{init:function(a,b){a.addCommand("mcePrint",function(){a.getWin().print()}),a.addButton("print",{title:"print.desc",cmd:"mcePrint"})},getInfo:function(){return{longname:"Print",author:"Moxiecode Systems AB",authorurl:"http://tinymce.moxiecode.com",infourl:"http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/print",version:tinymce.majorVersion+"."+tinymce.minorVersion}}}),tinymce.PluginManager.add("print",tinymce.plugins.Print)}();