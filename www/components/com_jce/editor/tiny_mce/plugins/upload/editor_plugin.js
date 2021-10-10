/* jce - 2.9.16 | 2021-09-20 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){function uid(){var i,guid=(new Date).getTime().toString(32);for(i=0;i<5;i++)guid+=Math.floor(65535*Math.random()).toString(32);return"wf_"+guid+(counter++).toString(32)}var each=tinymce.each,extend=tinymce.extend,JSON=tinymce.util.JSON,RangeUtils=tinymce.dom.RangeUtils,Dispatcher=tinymce.util.Dispatcher,counter=0,mimes={};!function(mime_data){var i,y,ext,items=mime_data.split(/,/);for(i=0;i<items.length;i+=2)for(ext=items[i+1].split(/ /),y=0;y<ext.length;y++)mimes[ext[y]]=items[i]}("application/msword,doc dot,application/pdf,pdf,application/pgp-signature,pgp,application/postscript,ps ai eps,application/rtf,rtf,application/vnd.ms-excel,xls xlb,application/vnd.ms-powerpoint,ppt pps pot,application/zip,zip,application/x-shockwave-flash,swf swfl,application/vnd.openxmlformats-officedocument.wordprocessingml.document,docx,application/vnd.openxmlformats-officedocument.wordprocessingml.template,dotx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,xlsx,application/vnd.openxmlformats-officedocument.presentationml.presentation,pptx,application/vnd.openxmlformats-officedocument.presentationml.template,potx,application/vnd.openxmlformats-officedocument.presentationml.slideshow,ppsx,application/x-javascript,js,application/json,json,audio/mpeg,mpga mpega mp2 mp3,audio/x-wav,wav,audio/mp4,m4a,image/bmp,bmp,image/gif,gif,image/jpeg,jpeg jpg jpe,image/photoshop,psd,image/png,png,image/svg+xml,svg svgz,image/tiff,tiff tif,text/plain,asc txt text diff log md,text/html,htm html xhtml,text/css,css,text/csv,csv,text/rtf,rtf,video/mpeg,mpeg mpg mpe,video/quicktime,qt mov,video/mp4,mp4,video/x-m4v,m4v,video/x-flv,flv,video/x-ms-wmv,wmv,video/avi,avi,video/webm,webm,video/vnd.rn-realvideo,rv,application/vnd.oasis.opendocument.formula-template,otf,application/octet-stream,exe");var state={STOPPED:1,STARTED:2,QUEUED:1,UPLOADING:2,FAILED:4,DONE:5,GENERIC_ERROR:-100,HTTP_ERROR:-200,IO_ERROR:-300,SECURITY_ERROR:-400};tinymce.create("tinymce.plugins.Upload",{files:[],plugins:[],init:function(ed,url){function cancel(){ed.dom.bind(ed.getBody(),"dragover",function(e){var dataTransfer=e.dataTransfer;dataTransfer&&dataTransfer.files&&dataTransfer.files.length&&e.preventDefault()}),ed.dom.bind(ed.getBody(),"drop",function(e){var dataTransfer=e.dataTransfer;dataTransfer&&dataTransfer.files&&dataTransfer.files.length&&e.preventDefault()})}var self=this;self.editor=ed,self.plugin_url=url,ed.onPreInit.add(function(){function isMediaPlaceholder(node){if("media"===node.name)return!0;if("img"===node.name){if(node.attr("data-mce-upload-marker"))return!0;var cls=node.attr("class");if(cls&&cls.indexOf("upload-placeholder")!=-1)return!0}return!1}function bindUploadEvents(ed){each(ed.dom.select(".mce-item-upload-marker",ed.getBody()),function(n){0==self.plugins.length?ed.dom.remove(n):self._bindUploadMarkerEvents(ed,n)})}each(ed.plugins,function(plg,name){if(tinymce.is(plg.getUploadConfig,"function")){var data=plg.getUploadConfig();data.inline&&data.filetypes&&self.plugins.push(plg)}}),ed.onBeforeSetContent.add(function(ed,o){o.content=o.content.replace(/<\/media>/g,"&nbsp;</media>")}),ed.onPostProcess.add(function(ed,o){o.content=o.content.replace(/(&nbsp;|\u00a0)<\/media>/g,"</media>")}),ed.schema.addCustomElements("~media[type|width|height|class|style|title|*]"),ed.settings.compress.css||ed.dom.loadCSS(url+"/css/content.css"),ed.serializer.addAttributeFilter("data-mce-marker",function(nodes,name,args){for(var i=nodes.length;i--;)nodes[i].remove()}),ed.parser.addNodeFilter("img,media",function(nodes){for(var node,i=nodes.length;i--;)node=nodes[i],isMediaPlaceholder(node)&&(0==self.plugins.length?node.remove():self._createUploadMarker(node))}),ed.serializer.addNodeFilter("img",function(nodes){for(var node,cls,i=nodes.length;i--;)node=nodes[i],cls=node.attr("class"),cls&&/mce-item-upload-marker/.test(cls)&&(cls=cls.replace(/(?:^|\s)(mce-item-)(?!)(upload|upload-marker|upload-placeholder)(?!\S)/g,""),node.attr({"data-mce-src":"",src:"",class:tinymce.trim(cls)}),node.name="media",node.shortEnded=!1,node.attr("alt",null))}),ed.selection.onSetContent.add(function(){bindUploadEvents(ed)}),ed.onSetContent.add(function(){bindUploadEvents(ed)}),ed.onFullScreen&&ed.onFullScreen.add(function(editor){bindUploadEvents(editor)})}),ed.onInit.add(function(){function cancelEvent(e){e.preventDefault(),e.stopPropagation()}return 0==self.plugins.length?void cancel():(ed.theme&&ed.theme.onResolveName&&ed.theme.onResolveName.add(function(theme,o){var n=o.node;n&&"IMG"===n.nodeName&&/mce-item-upload/.test(n.className)&&(o.name="placeholder")}),ed.dom.bind(ed.getBody(),"dragover",function(e){e.dataTransfer.dropEffect=tinymce.VK.metaKeyPressed(e)?"copy":"move"}),void ed.dom.bind(ed.getBody(),"drop",function(e){var dataTransfer=e.dataTransfer;dataTransfer&&dataTransfer.files&&dataTransfer.files.length&&(each(dataTransfer.files,function(file){var rng=RangeUtils.getCaretRangeFromPoint(e.clientX,e.clientY,ed.getDoc());rng&&(ed.selection.setRng(rng),rng=null),self.addFile(file)}),cancelEvent(e)),self.files.length&&each(self.files,function(file){self.upload(file)}),tinymce.isGecko&&"IMG"==e.target.nodeName&&cancelEvent(e)}))}),self.FilesAdded=new Dispatcher(this),self.UploadProgress=new Dispatcher(this),self.FileUploaded=new Dispatcher(this),self.UploadError=new Dispatcher(this),this.settings={multipart:!0,multi_selection:!0,file_data_name:"file",filters:[]},self.FileUploaded.add(function(file,o){function showError(error){return ed.windowManager.alert(error||ed.getLang("upload.response_error","Invalid Upload Response")),ed.dom.remove(n),!1}var n=file.marker;if(n){if(!o||!o.response)return showError();var r,data=o.response;try{r=JSON.parse(data)}catch(e){return data.indexOf("{")!==-1&&(data="The server returned an invalid JSON response."),showError(data)}if(!r)return showError();if(r.error||!r.result){var txt="";return r.error&&(txt=r.error.message||""),ed.windowManager.alert(txt),ed.dom.remove(n),!1}if(file.status==state.DONE){if(file.uploader){var files=r.result.files||[],item=files.length?files[0]:{},obj=tinymce.extend({type:file.type,name:file.name},item);self._selectAndInsert(file,obj)}self.files.splice(tinymce.inArray(self.files,file),1)}ed.dom.remove(n)}}),self.UploadProgress.add(function(file){if(file.loaded&&file.marker){var pct=Math.floor(file.loaded/file.size*100);ed.dom.setAttrib(file.marker,"data-progress",pct)}}),self.UploadError.add(function(o){ed.windowManager.alert(o.code+" : "+o.message),o.file&&o.file.marker&&ed.dom.remove(o.file.marker)})},_selectAndInsert:function(file,data){var ed=this.editor,marker=file.marker,uploader=file.uploader;ed.selection.select(marker);var elm=uploader.insertUploadedFile(data);if(elm){if("object"==typeof elm&&elm.nodeType){if(ed.dom.hasClass(marker,"mce-item-upload-marker")){var styles=ed.dom.getAttrib(marker,"data-mce-style"),w=marker.width||0,h=marker.height||0;styles&&(styles=ed.dom.styles.parse(styles),styles.width&&(w=styles.width,delete styles.width),styles.height&&(h=styles.height,delete styles.height),ed.dom.setStyles(elm,styles)),w&&ed.dom.setAttrib(elm,"width",w),h&&(w&&(h=""),ed.dom.setAttrib(elm,"height",h))}ed.undoManager.add(),ed.dom.replace(elm,marker)}return ed.nodeChanged(),!0}},_bindUploadMarkerEvents:function(ed,marker){function removeUpload(){dom.setStyles("wf_upload_button",{top:"",left:"",display:"none",zIndex:""})}var self=this,dom=tinymce.DOM;ed.onNodeChange.add(removeUpload),ed.dom.bind(ed.getWin(),"scroll",removeUpload);var input=dom.get("wf_upload_input"),btn=dom.get("wf_upload_button");btn||(btn=dom.add(dom.doc.body,"div",{id:"wf_upload_button",class:"btn",role:"button",title:ed.getLang("upload.button_description","Click to upload a file")},'<label for="wf_upload_input"><span class="icon-upload"></span>&nbsp;'+ed.getLang("upload.label","Upload")+"</label>"),input=dom.add(btn,"input",{type:"file",id:"wf_upload_input"})),ed.dom.bind(marker,"mouseover",function(e){if(!ed.dom.getAttrib(marker,"data-mce-selected")){var vp=ed.dom.getViewPort(ed.getWin()),p1=dom.getRect(ed.getContentAreaContainer()),p2=ed.dom.getRect(marker);if(!(vp.y>p2.y+p2.h/2-25||vp.y<p2.y+p2.h/2+25-p1.h)){var x=Math.max(p2.x-vp.x,0)+p1.x,y=Math.max(p2.y-vp.y,0)+p1.y-Math.max(vp.y-p2.y,0),zIndex="mce_fullscreen"==ed.id?dom.get("mce_fullscreen_container").style.zIndex:0;dom.setStyles("wf_upload_button",{top:y+p2.h/2-16,left:x+p2.w/2-50,display:"block",zIndex:zIndex+1}),dom.setStyles("wf_select_button",{top:y+p2.h/2-16,left:x+p2.w/2-50,display:"block",zIndex:zIndex+1}),input.onchange=function(){if(input.files){var file=input.files[0];file&&(file.marker=marker,self.addFile(file)&&(each(["width","height"],function(key){ed.dom.setStyle(marker,key,ed.dom.getAttrib(marker,key))}),file.marker=ed.dom.rename(marker,"span"),self.upload(file),removeUpload()))}}}}}),ed.dom.bind(marker,"mouseout",function(e){!e.relatedTarget&&e.clientY>0||removeUpload()})},_createUploadMarker:function(n){var styles,ed=this.editor,src=n.attr("src")||"",style={},cls=[];if(!n.attr("alt")&&!/data:image/.test(src)){var alt=src.substring(src.length,src.lastIndexOf("/")+1);n.attr("alt",alt)}n.attr("style")&&(style=ed.dom.styles.parse(n.attr("style"))),n.attr("hspace")&&(style["margin-left"]=style["margin-right"]=n.attr("hspace")),n.attr("vspace")&&(style["margin-top"]=style["margin-bottom"]=n.attr("vspace")),n.attr("align")&&(style.float=n.attr("align")),n.attr("class")&&(cls=n.attr("class").replace(/\s*upload-placeholder\s*/,"").split(" ")),cls.push("mce-item-upload"),cls.push("mce-item-upload-marker"),"media"===n.name&&(n.name="img",n.shortEnded=!0),n.attr({src:"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",class:tinymce.trim(cls.join(" "))});var tmp=ed.dom.create("span",{style:style}),styles=ed.dom.getAttrib(tmp,"style");styles&&n.attr({style:styles,"data-mce-style":styles})},buildUrl:function(url,items){var query="";return each(items,function(value,name){query+=(query?"&":"")+encodeURIComponent(name)+"="+encodeURIComponent(value)}),query&&(url+=(url.indexOf("?")>0?"&":"?")+query),url},addFile:function(file){var ed=this.editor,self=this;if(/\.(php|php(3|4|5)|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)\./i.test(file.name))return ed.windowManager.alert(ed.getLang("upload.file_extension_error","File type not supported")),!1;if(each(self.plugins,function(o,k){if(!file.upload_url){var url=o.getUploadURL(file);url&&(file.upload_url=url,file.uploader=o)}}),file.upload_url){if(tinymce.is(file.uploader.getUploadConfig,"function")){var config=file.uploader.getUploadConfig();if(!new RegExp(".("+config.filetypes.join("|")+")$","i").test(file.name))return ed.windowManager.alert(ed.getLang("upload.file_extension_error","File type not supported")),!1;if(file.size){var max=parseInt(config.max_size,10)||1024;if(file.size>1024*max)return ed.windowManager.alert(ed.getLang("upload.file_size_error","File size exceeds maximum allowed size")),!1}}if(self.FilesAdded.dispatch(file),!file.marker){ed.execCommand("mceInsertContent",!1,'<span data-mce-marker="1" id="__mce_tmp">\ufeff</span>',{skip_undo:1});var w,h,n=ed.dom.get("__mce_tmp");/image\/(gif|png|jpeg|jpg)/.test(file.type)?(w=h=Math.round(Math.sqrt(file.size)),w=Math.max(300,w),h=Math.max(300,h),ed.dom.setStyles(n,{width:w,height:h}),ed.dom.addClass(n,"mce-item-upload")):ed.setProgressState(!0),file.marker=n}return ed.undoManager.add(),self.files.push(file),!0}return ed.windowManager.alert(ed.getLang("upload.file_extension_error","File type not supported")),!1},upload:function(file){function sendFile(bin){var xhr=new XMLHttpRequest,formData=new FormData;xhr.upload&&(xhr.upload.onprogress=function(e){e.lengthComputable&&(file.loaded=Math.min(file.size,e.loaded),self.UploadProgress.dispatch(file))}),xhr.onreadystatechange=function(){var httpStatus;if(4==xhr.readyState&&self.state!==state.STOPPED){ed.setProgressState(!1);try{httpStatus=xhr.status}catch(ex){httpStatus=0}httpStatus>=400?self.UploadError.dispatch({code:state.HTTP_ERROR,message:ed.getLang("upload.http_error","HTTP Error"),file:file,status:httpStatus}):(file.loaded=file.size,self.UploadProgress.dispatch(file),bin=formData=null,file.status=state.DONE,self.FileUploaded.dispatch(file,{response:xhr.responseText,status:httpStatus}))}};var name=file.target_name||file.name;name=name.replace(/[\+\\\/\?\#%&<>"\'=\[\]\{\},;@\^\(\)£€$~]/g,""),extend(args,{name:name}),xhr.open("post",url,!0),each(self.settings.headers,function(value,name){xhr.setRequestHeader(name,value)}),each(extend(args,self.settings.multipart_params),function(value,name){formData.append(name,value)}),formData.append(self.settings.file_data_name,bin),xhr.send(formData)}var self=this,ed=this.editor,args={method:"upload",id:uid(),inline:1},url=file.upload_url;url+="&"+ed.settings.query,file.status!=state.DONE&&file.status!=state.FAILED&&self.state!=state.STOPPED&&(extend(args,{name:file.target_name||file.name}),sendFile(file))}}),tinymce.PluginManager.add("upload",tinymce.plugins.Upload)}();