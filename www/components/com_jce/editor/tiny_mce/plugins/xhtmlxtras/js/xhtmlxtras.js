/* jce - 2.9.27 | 2022-06-23 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2022 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($){function isRootNode(ed,node){return node==ed.getBody()||tinymce.util.isFakeRoot(node)}var mediaApi,XHTMLXtrasDialog={settings:{},getAttributes:function(n){var i,ed=tinyMCEPopup.editor,attrs=n.attributes,attribs={};for(i=attrs.length-1;i>=0;i--){var name=attrs[i].name,value=ed.dom.getAttrib(n,name);"_"!==name.charAt(0)&&name.indexOf("-mce-")===-1&&(attribs[name]=value)}return mediaApi&&mediaApi.isMediaObject(n)&&(attribs=mediaApi.getMediaData()),attribs},init:function(){var ed=tinyMCEPopup.editor,se=ed.selection,n=se.getNode(),element=tinyMCEPopup.getWindowArg("element");if(ed.plugins.media&&(mediaApi=ed.plugins.media),element&&(n=ed.dom.getParent(n,element)),Wf.init(),n&&!isRootNode(ed,n)){var attribs=this.getAttributes(n);if(!$.isEmptyObject(attribs)){$(":input").each(function(){var k=$(this).attr("id");/on(click|dblclick)/.test(k)&&(k="data-mce-"+k),"classes"===k&&(k="class");var v=attribs[k];tinymce.is(v)&&("class"==k&&(v=v.replace(/mce-(\S+)/g,"").replace(/\s+/g," ").trim()),$(this).val(v).trigger("change"),delete attribs[k])});var x=0;$.each(attribs,function(k,v){if("data-mouseover"===k||"data-mouseout"===k||0===k.indexOf("on"))return!0;try{v=decodeURIComponent(v)}catch(e){}var repeatable=$(".uk-repeatable").eq(0);x>0&&$(repeatable).clone(!0).appendTo($(repeatable).parent());var elements=$(".uk-repeatable").eq(x).find("input, select");$(elements).eq(0).val(k),$(elements).eq(1).val(v),x++}),$("#insert").button("option","label",ed.getLang("update","Insert"))}}$("#remove").button({icons:{primary:"uk-icon-minus-circle"}}).toggle(!!element),"html4"===ed.settings.schema&&ed.settings.validate===!0&&$("input.html5").parents(".uk-form-row").hide(),tinymce.is(n,":input, form")||$("input.form").parents(".uk-form-row").hide(),n&&"IMG"!==n.nodeName&&$("input.media").parents(".uk-form-row").hide(),$(".uk-form-controls select").datalist().trigger("datalist:update"),$(".uk-datalist").trigger("datalist:update"),$(".uk-repeatable").on("repeatable:delete",function(e,ctrl,elm){$(elm).find("input, select").eq(1).val("")})},insert:function(){var elm,ed=tinyMCEPopup.editor,se=ed.selection,n=se.getNode();tinyMCEPopup.restoreSelection();var element=tinyMCEPopup.getWindowArg("element"),args={},attribs=this.getAttributes(n);if($(":input").not("input[name]").each(function(){var k=$(this).attr("id"),v=$(this).val();/on(click|dblclick)/.test(k)&&(k="data-mce-"+k),"classes"===k&&(k="class",v=$.trim(v)),args[k]=v,delete attribs[k]}),$(".uk-repeatable").each(function(){var elements=$("input, select",this),key=$(elements).eq(0).val(),value=$(elements).eq(1).val();key&&(args[key]=value,delete attribs[key])}),$.each(attribs,function(key,value){args[key]=""}),element)elm=n.nodeName.toLowerCase()==element?n:ed.dom.getParent(n,element),ed.formatter.apply(element.toLowerCase(),args,elm);else{var isTextSelection=!se.isCollapsed()&&se.getContent()==se.getContent({format:"text"});isRootNode(ed,n)||isTextSelection?ed.formatter.apply("attributes",args):mediaApi&&mediaApi.isMediaObject(n)?mediaApi.updateMedia(args):ed.dom.setAttribs(n,args)}ed.undoManager.add(),tinyMCEPopup.close()},remove:function(){var ed=tinyMCEPopup.editor,element=tinyMCEPopup.getWindowArg("element");element&&(ed.formatter.remove(element),ed.undoManager.add()),tinyMCEPopup.close()},insertDateTime:function(id){document.getElementById(id).value=this.getDateTime(new Date,"%Y-%m-%dT%H:%M:%S")},getDateTime:function(d,fmt){return fmt=fmt.replace("%D","%m/%d/%y"),fmt=fmt.replace("%r","%I:%M:%S %p"),fmt=fmt.replace("%Y",""+d.getFullYear()),fmt=fmt.replace("%y",""+d.getYear()),fmt=fmt.replace("%m",this.addZeros(d.getMonth()+1,2)),fmt=fmt.replace("%d",this.addZeros(d.getDate(),2)),fmt=fmt.replace("%H",""+this.addZeros(d.getHours(),2)),fmt=fmt.replace("%M",""+this.addZeros(d.getMinutes(),2)),fmt=fmt.replace("%S",""+this.addZeros(d.getSeconds(),2)),fmt=fmt.replace("%I",""+((d.getHours()+11)%12+1)),fmt=fmt.replace("%p",""+(d.getHours()<12?"AM":"PM")),fmt=fmt.replace("%%","%")},addZeros:function(value,len){var i;if(value=""+value,value.length<len)for(i=0;i<len-value.length;i++)value="0"+value;return value}};window.XHTMLXtrasDialog=XHTMLXtrasDialog,tinyMCEPopup.onInit.add(XHTMLXtrasDialog.init,XHTMLXtrasDialog)}(jQuery);