/* jce - 2.9.3 | 2021-02-25 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){var DOM=tinymce.DOM;tinymce.create("tinymce.themes.NoSkin",{init:function(ed,url){function grabContent(){var n,or,r,se=ed.selection;n=ed.dom.add(ed.getBody(),"div",{id:"_mcePaste",style:"position:absolute;left:-1000px;top:-1000px"},'<br mce_bogus="1" />').firstChild,or=ed.selection.getRng(),r=ed.getDoc().createRange(),r.setStart(n,0),r.setEnd(n,0),se.setRng(r),window.setTimeout(function(){var n=ed.dom.get("_mcePaste");h=n.innerHTML,ed.dom.remove(n),se.setRng(or),h=h.replace(/<\/?\w+[^>]*>/gi,""),el=ed.dom.create("div",0,h),tinymce.each(ed.dom.select("span",el).reverse(),function(n){if(ed.dom.getAttribs(n).length<=1&&""===n.className)return ed.dom.remove(n,1)}),ed.execCommand("mceInsertContent",!1,ed.serializer.serialize(el,{getInner:1}))},0)}var t=this,s=ed.settings;t.editor=ed,ed.onInit.add(function(){ed.onBeforeExecCommand.add(function(ed,cmd,ui,val,o){return o.terminate=!0,!1}),ed.dom.loadCSS(url+"/skins/default/content.css")}),ed.onKeyDown.add(function(ed,e){(e.ctrlKey&&86==e.keyCode||e.shiftKey&&45==e.keyCode)&&grabContent()}),ed.onKeyDown.add(function(ed,e){if(e.ctrlKey&&66==e.keyCode||e.ctrlKey&&73==e.keyCode||e.ctrlKey&&85==e.keyCode)return tinymce.dom.Event.cancel(e)}),ed.settings.compress.css||DOM.loadCSS(s.editor_css?ed.documentBaseURI.toAbsolute(s.editor_css):url+"/skins/default/ui.css")},renderUI:function(o){var ic,sc,t=this,n=o.targetNode,ed=t.editor;ed.controlManager;return n=p=DOM.create("div",{role:"application","aria-labelledby":ed.id+"_voice",id:ed.id+"_parent",class:"mceEditor mceDefaultSkin"+("rtl"==ed.settings.directionality?" mceRtl":"")}),DOM.add(n,"span",{class:"mceVoiceLabel",style:"display:none;",id:ed.id+"_voice"},ed.settings.aria_label),n=sc=DOM.add(n,"div",{role:"presentation",id:ed.id+"_tbl",class:"mceLayout"}),ic=DOM.add(n,"div",{class:"mceIframeContainer"}),n=o.targetNode,DOM.insertAfter(p,n),{iframeContainer:ic,editorContainer:ed.id+"_parent",sizeContainer:sc,deltaHeight:-20}}}),tinymce.ThemeManager.add("none",tinymce.themes.NoSkin)}();