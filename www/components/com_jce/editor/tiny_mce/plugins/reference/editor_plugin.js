/* jce - 2.9.3 | 2021-02-25 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){var each=tinymce.each;tinymce.DOM;tinymce.create("tinymce.plugins.XHTMLXtrasPlugin",{init:function(ed,url){this.editor=ed,ed.addCommand("mceCite",function(){ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=xhtmlxtras&element=cite",width:480+parseInt(ed.getLang("xhtmlxtras.cite_delta_width",0)),height:340+parseInt(ed.getLang("xhtmlxtras.cite_delta_height",0)),inline:1,size:"mce-modal-portrait-medium"},{plugin_url:url,element:"cite"})}),ed.addCommand("mceAcronym",function(){ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=xhtmlxtras&element=acronym",width:480+parseInt(ed.getLang("xhtmlxtras.acronym_delta_width",0)),height:340+parseInt(ed.getLang("xhtmlxtras.acronym_delta_height",0)),inline:1,size:"mce-modal-portrait-medium"},{plugin_url:url,element:"acronym"})}),ed.addCommand("mceAbbr",function(){ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=xhtmlxtras&element=abbr",width:480+parseInt(ed.getLang("xhtmlxtras.abbr_delta_width",0)),height:340+parseInt(ed.getLang("xhtmlxtras.abbr_delta_height",0)),inline:1,size:"mce-modal-portrait-medium"},{plugin_url:url,element:"abbr"})}),ed.addCommand("mceDel",function(){ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=xhtmlxtras&element=del",width:480+parseInt(ed.getLang("xhtmlxtras.del_delta_width",0)),height:380+parseInt(ed.getLang("xhtmlxtras.del_delta_height",0)),inline:1,size:"mce-modal-portrait-medium"},{plugin_url:url,element:"del"})}),ed.addCommand("mceIns",function(){ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=xhtmlxtras&element=ins",width:480+parseInt(ed.getLang("xhtmlxtras.ins_delta_width",0)),height:380+parseInt(ed.getLang("xhtmlxtras.ins_delta_height",0)),inline:1,size:"mce-modal-portrait-medium"},{plugin_url:url,element:"ins"})}),ed.addCommand("mceAttributes",function(){ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=xhtmlxtras&element=attributes",width:640,height:520,inline:1},{plugin_url:url})}),ed.addButton("cite",{title:"xhtmlxtras.cite_desc",cmd:"mceCite"}),"html5-strict"!==ed.settings.schema&&ed.addButton("acronym",{title:"xhtmlxtras.acronym_desc",cmd:"mceAcronym"}),ed.addButton("abbr",{title:"xhtmlxtras.abbr_desc",cmd:"mceAbbr"}),ed.addButton("del",{title:"xhtmlxtras.del_desc",cmd:"mceDel"}),ed.addButton("ins",{title:"xhtmlxtras.ins_desc",cmd:"mceIns"}),ed.addButton("attribs",{title:"xhtmlxtras.attribs_desc",cmd:"mceAttributes"}),ed.onNodeChange.add(function(ed,cm,n,co){if(n=ed.dom.getParent(n,"CITE,ACRONYM,ABBR,DEL,INS"),cm.setDisabled("cite",co),cm.setDisabled("acronym",co),cm.setDisabled("abbr",co),cm.setDisabled("del",co),cm.setDisabled("ins",co),cm.setDisabled("attribs",n&&"BODY"==n.nodeName),cm.setActive("cite",0),cm.setActive("acronym",0),cm.setActive("abbr",0),cm.setActive("del",0),cm.setActive("ins",0),n)do cm.setDisabled(n.nodeName.toLowerCase(),0),cm.setActive(n.nodeName.toLowerCase(),1);while(n=n.parentNode)}),ed.onPreInit.add(function(){ed.dom.create("abbr"),ed.formatter.register({cite:{inline:"cite",remove:"all",onformat:function(elm,fmt,vars){each(vars,function(value,key){ed.dom.setAttrib(elm,key,value)})}},acronym:{inline:"acronym",remove:"all",onformat:function(elm,fmt,vars){each(vars,function(value,key){ed.dom.setAttrib(elm,key,value)})}},abbr:{inline:"abbr",remove:"all",onformat:function(elm,fmt,vars){each(vars,function(value,key){ed.dom.setAttrib(elm,key,value)})}},del:{inline:"del",remove:"all",onformat:function(elm,fmt,vars){each(vars,function(value,key){ed.dom.setAttrib(elm,key,value)})}},ins:{inline:"ins",remove:"all",onformat:function(elm,fmt,vars){each(vars,function(value,key){ed.dom.setAttrib(elm,key,value)})}},attributes:{inline:"span",remove:"all",onformat:function(elm,fmt,vars){each(vars,function(value,key){ed.dom.setAttrib(elm,key,value)})}}})})}}),tinymce.PluginManager.add("xhtmlxtras",tinymce.plugins.XHTMLXtrasPlugin)}();