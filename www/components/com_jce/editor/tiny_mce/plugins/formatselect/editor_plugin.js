/* jce - 2.9.7 | 2021-05-13 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){var each=tinymce.each,fmts={p:"advanced.paragraph",address:"advanced.address",pre:"advanced.pre",h1:"advanced.h1",h2:"advanced.h2",h3:"advanced.h3",h4:"advanced.h4",h5:"advanced.h5",h6:"advanced.h6",div:"advanced.div",div_container:"advanced.div_container",blockquote:"advanced.blockquote",code:"advanced.code",samp:"advanced.samp",span:"advanced.span",section:"advanced.section",article:"advanced.article",aside:"advanced.aside",header:"advanced.header",footer:"advanced.footer",nav:"advanced.nav",figure:"advanced.figure",dt:"advanced.dt",dd:"advanced.dd"};tinymce.create("tinymce.plugins.FormatSelectPlugin",{init:function(ed,url){function isFormat(n){return tinymce.inArray(nodes,n.nodeName)!==-1&&(!n.className||n.className.indexOf("mce-item-")===-1)}this.editor=ed;var nodes=[];each(ed.getParam("formatselect_blockformats",fmts,"hash"),function(value,key){"span"!==key&&nodes.push(key.toUpperCase())}),ed.onNodeChange.add(function(ed,cm,n){var p,c=cm.get("formatselect"),value="";c&&(p=ed.dom.getParent(n,isFormat,ed.getBody()),p&&p.nodeName&&(value=p.nodeName.toLowerCase(),"pre"===value&&(value=p.getAttribute("data-mce-code")||p.getAttribute("data-mce-type")||value)),c.select(value))})},createControl:function(n,cf){if("formatselect"===n)return this._createBlockFormats()},_createBlockFormats:function(){var ctrl,ed=this.editor,PreviewCss=tinymce.util.PreviewCss;return ctrl=ed.controlManager.createListBox("formatselect",{title:"advanced.block",max_height:384,onselect:function(v){return ed.execCommand("FormatBlock",!1,v),!1}}),ctrl&&each(ed.getParam("formatselect_blockformats",fmts,"hash"),function(value,key){ctrl.add(ed.getLang(value,key),key,{class:"mce_formatPreview mce_"+key,style:function(){return PreviewCss(ed,{block:key})}})}),ctrl}}),tinymce.PluginManager.add("formatselect",tinymce.plugins.FormatSelectPlugin)}();