/* jce - 2.9.7 | 2021-05-13 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){function isPreviewMedia(type){return"iframe"===type||"video"===type||"audio"===type}function isObjectEmbed(type){return!isPreviewMedia(type)}function isSupportedMedia(url){return/youtu(\.)?be(.+)?\/(.+)/.test(url)?"youtube":/vimeo(.+)?\/(.+)/.test(url)?"vimeo":/dai\.?ly(motion)?(\.com)?/.test(url)?"dailymotion":/scribd\.com\/(.+)/.test(url)?"scribd":/slideshare\.net\/(.+)\/(.+)/.test(url)?"slideshare":/soundcloud\.com\/(.+)/.test(url)?"soundcloud":/spotify\.com\/(.+)/.test(url)?"spotify":/ted\.com\/talks\/(.+)/.test(url)?"ted":!!/twitch\.tv\/(.+)/.test(url)&&"twitch"}function parseHTML(value){var nodes=[];new SaxParser({start:function(name,attrs){"source"===name&&attrs.map?nodes.push({name:name,value:attrs.map}):"param"===name?nodes.push({name:name,value:attrs.map}):"embed"===name?nodes.push({name:name,value:attrs.map}):"track"===name&&nodes.push({name:name,value:attrs.map})}}).parse(value);var settings={invalid_elements:"source,param,embed,track",forced_root_block:!1,verify_html:!0,validate:!0},schema=new tinymce.html.Schema(settings),content=new Serializer(settings,schema).serialize(new DomParser(settings,schema).parse(value));return nodes.push({name:"html",value:content}),nodes}function isUrlValue(name){return tinymce.inArray(["src","data","movie","url","source"],name)!==-1}function cleanClassValue(value){return value&&(value=value.replace(/\s?mce-([\w-]+)/g,"").replace(/\s+/g," "),value=tinymce.trim(value),value=value.length>0?value:null),value||null}function processNodeAttributes(editor,tag,node){var attribs={},boolAttrs=editor.schema.getBoolAttrs();for(var key in node.attributes.map){var value=node.attributes.map[key];"src"===key&&"img"===node.name||"draggable"!==key&&"contenteditable"!==key&&0!==key.indexOf("on")&&(0===key.indexOf("data-mce-p-")&&(key=key.substr(11)),"data-mce-width"!==key&&"data-mce-height"!==key||(key=key.substr(9)),(editor.schema.isValid(tag,key)||key.indexOf("-")!==-1)&&("class"===key&&(value=cleanClassValue(value)),"src"!==key&&"poster"!==key&&"data"!==key||(value=editor.convertURL(value)),boolAttrs[key]&&(value=key),attribs[key]=value))}if(!node.attr("data")){var params=node.getAll("param");if(params.length){var param=params[0],value=param.attr("src")||param.attr("url")||null;value&&(attribs.src=editor.convertURL(value),param.remove())}}return attribs}function nodeToMedia(editor,node){var elm,tag=node.attr("data-mce-object"),attribs={};elm=new Node(tag,1),/\s*mce-object-preview\s*/.test(node.attr("class"))&&node.firstChild&&node.firstChild.name===tag&&(node=node.firstChild),attribs=processNodeAttributes(editor,tag,node),elm.attr(attribs);var html=node.attr("data-mce-html");if(html){var childNodes=parseHTML(unescape(html));each(childNodes,function(child){var inner;if("html"===child.name){var inner=new Node("#text",3);inner.raw=!0,inner.value=sanitize(editor,child.value),elm.append(inner)}else{var inner=new Node(child.name,1);"embed"!=child.name&&(inner.shortEnded=!0),each(child.value,function(val,key){htmlSchema.isValid(inner.name,key)&&inner.attr(key,val)}),elm.append(inner),"source"==inner.name&&inner.attr("src")==elm.attr("src")&&elm.attr("src",null)}})}if(elm.attr("data-mce-html",null),"object"===tag&&0===elm.getAll("embed").length&&"application/x-shockwave-flash"!==elm.attr("type")){var embed=new Node("embed",1);each(attribs,function(value,name){"data"===name&&embed.attr("src",value),htmlSchema.isValid("embed",name)&&embed.attr(name,value)}),elm.append(embed)}return elm}function htmlToData(ed,mediatype,html){var data={};try{html=unescape(html)}catch(e){}var nodes=parseHTML(html);return each(nodes,function(node,i){if("source"===node.name){data.source||(data.source=[]);var val=ed.convertURL(node.value.src);data.source.push(val)}else"param"===node.name?(isUrlValue(node.value.name)&&(node.value.value=ed.convertURL(node.value.value)),data[node.value.name]=node.value.value):data.html=node.value}),data}function isMediaObject(ed,node){return node=node||ed.selection.getNode(),ed.dom.getParent(node,"[data-mce-object]")}var each=tinymce.each,extend=tinymce.extend,Node=tinymce.html.Node,VK=tinymce.VK,Serializer=tinymce.html.Serializer,DomParser=tinymce.html.DomParser,SaxParser=tinymce.html.SaxParser,htmlSchema=new tinymce.html.Schema({schema:"mixed"}),isAbsoluteUrl=function(url){return url&&(url.indexOf("://")>0||0===url.indexOf("//"))},isLocalUrl=function(editor,url){if(isAbsoluteUrl(url)){var relative=editor.documentBaseURI.toRelative(url);return isAbsoluteUrl(relative)===!1}return!0},validateIframe=function(editor,node){var src=node.attr("src");return!!src&&(editor.settings.iframes_allow_supported?!!isLocalUrl(editor,src)||isSupportedMedia(src)!==!1:!editor.settings.iframes_allow_local||isLocalUrl(editor,src))},sanitize=function(editor,html){var blocked,writer=new tinymce.html.Writer;return new tinymce.html.SaxParser({validate:!1,allow_conditional_comments:!1,special:"script,noscript",comment:function(text){writer.comment(text)},cdata:function(text){writer.cdata(text)},text:function(text,raw){writer.text(text,raw)},start:function(name,attrs,empty){if(blocked=!0,"script"!==name&&"noscript"!==name&&"svg"!==name){for(var i=attrs.length-1;i>=0;i--){var attrName=attrs[i].name;0===attrName.indexOf("on")&&(delete attrs.map[attrName],attrs.splice(i,1)),"style"===attrName&&(attrs[i].value=editor.dom.serializeStyle(editor.dom.parseStyle(attrs[i].value),name))}writer.start(name,attrs,empty),blocked=!1}},end:function(name){blocked||writer.end(name)}},htmlSchema).parse(html),writer.getContent()},mediaTypes={flash:{classid:"CLSID:D27CDB6E-AE6D-11CF-96B8-444553540000",type:"application/x-shockwave-flash",codebase:"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,1,53,64"},shockwave:{classid:"CLSID:166B1BCA-3F9C-11CF-8075-444553540000",type:"application/x-director",codebase:"http://download.macromedia.com/pub/shockwave/cabs/director/sw.cab#version=10,2,0,023"},windowsmedia:{classid:"CLSID:6BF52A52-394A-11D3-B153-00C04F79FAA6",type:"application/x-mplayer2",codebase:"http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=10,00,00,3646"},quicktime:{classid:"CLSID:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B",type:"video/quicktime",codebase:"http://www.apple.com/qtactivex/qtplugin.cab#version=7,3,0,0"},divx:{classid:"CLSID:67DABFBF-D0AB-41FA-9C46-CC0F21721616",type:"video/divx",codebase:"http://go.divx.com/plugin/DivXBrowserPlugin.cab"},realmedia:{classid:"CLSID:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA",type:"audio/x-pn-realaudio-plugin"},java:{classid:"CLSID:8AD9C840-044E-11D1-B3E9-00805F499D93",type:"application/x-java-applet",codebase:"http://java.sun.com/products/plugin/autodl/jinstall-1_5_0-windows-i586.cab#Version=1,5,0,0"},silverlight:{classid:"CLSID:DFEAF541-F3E1-4C24-ACAC-99C30715084A",type:"application/x-silverlight-2"},video:{type:"video/mpeg"},audio:{type:"audio/mpeg"},iframe:{}},lookup={},mimes={};!function(data){var i,y,ext,items=data.split(/,/);for(i=0;i<items.length;i+=2)for(ext=items[i+1].split(/ /),y=0;y<ext.length;y++)mimes[ext[y]]=items[i]}("application/x-director,dcr,video/divx,divx,application/pdf,pdf,application/x-shockwave-flash,swf swfl,audio/mpeg,mpga mpega mp2 mp3,audio/ogg,ogg spx oga,audio/x-wav,wav,video/mpeg,mpeg mpg mpe,video/mp4,mp4 m4v,video/ogg,ogg ogv,video/webm,webm,video/quicktime,qt mov,video/x-flv,flv,video/vnd.rn-realvideo,rv","NaNvideo/x-matroska,mkv"),each(mediaTypes,function(value,key){value.name=key,value.classid&&(lookup[value.classid]=value),value.type&&(lookup[value.type]=value),lookup[key.toLowerCase()]=value});var createPlaceholderNode=function(editor,node){var placeHolder;return placeHolder=new Node("img",1),placeHolder.shortEnded=!0,retainAttributesAndInnerHtml(editor,node,placeHolder),placeHolder.attr({src:"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7","data-mce-object":node.name}),placeHolder},previewToPlaceholder=function(editor,node){var obj=new tinymce.html.DomParser({},editor.schema).parse(node.innerHTML),ifr=obj.firstChild,placeholder=createPlaceholderNode(editor,ifr),html=(new tinymce.html.Serializer).serialize(placeholder);editor.dom.replace(editor.dom.createFragment(html),node)},placeholderToPreview=function(editor,node){var name,placeholder=new Node("img",1);placeholder.shortEnded=!0;for(var attributes=node.attributes,i=attributes.length;i--;)name=attributes[i].nodeName,placeholder.attr(name,""+node.getAttribute(name));var elm=nodeToMedia(editor,placeholder),preview=createPreviewNode(editor,elm),html=(new tinymce.html.Serializer).serialize(preview);editor.dom.replace(editor.dom.createFragment(html),node)},createPreviewNode=function(editor,node){var previewWrapper,previewNode,shimNode,name=node.name,msg=editor.getLang("media.preview_hint","Click to activate, %s + Click to toggle placeholder");msg=msg.replace(/%s/g,tinymce.isMac?"CMD":"CTRL"),node.attr("autoplay")&&(node.attr("data-mce-p-autoplay",node.attr("autoplay")),node.attr("autoplay",null));var canResize=function(node){return"video"===node.name?"proportional":"iframe"===node.name?isSupportedMedia(node.attr("src"))?"proportional":"true":"false"},styles={},styleVal=editor.dom.parseStyle(node.attr("style"));return each(["width","height"],function(key){val=node.attr(key)||styleVal[key]||"",val&&!/(%|[a-z]{1,3})$/.test(val)&&(val+="px"),styles[key]=val}),previewWrapper=Node.create("span",{contentEditable:"false","data-mce-object":name,class:"mce-object-preview mce-object-"+name,"aria-details":msg,"data-mce-resize":canResize(node),style:editor.dom.serializeStyle(styles)}),previewNode=Node.create(name,{src:node.attr("src")}),retainAttributesAndInnerHtml(editor,node,previewNode),shimNode=Node.create("span",{class:"mce-object-shim"}),previewWrapper.append(previewNode),previewWrapper.append(shimNode),previewWrapper},convertPlaceholderToMedia=function(editor,node){var elm=nodeToMedia(editor,node);isObjectEmbed(elm.name)||node.empty(),node.replace(elm),node.empty()},retainAttributesAndInnerHtml=function(editor,sourceNode,targetNode){var attrName,attrValue,attribs,ai,innerHtml,styles,style=editor.dom.parseStyle(sourceNode.attr("style")),width=sourceNode.attr("width")||style.width||"",height=sourceNode.attr("height")||style.height||"",style=editor.dom.parseStyle(sourceNode.attr("style")),legacyAttributes=["bgcolor","align","border","vspace","hspace"];for(tinymce.each(legacyAttributes,function(na){var v=sourceNode.attr(na);if(v){switch(na){case"bgcolor":style["background-color"]=v;break;case"align":/^(left|right)$/.test(v)?style.float=v:style["vertical-align"]=v;break;case"vspace":style["margin-top"]=v,style["margin-bottom"]=v;break;case"hspace":style["margin-left"]=v,style["margin-right"]=v;break;default:style[na]=v}sourceNode.attr(na,null)}}),attribs=sourceNode.attributes,ai=attribs.length;ai--;)attrName=attribs[ai].name,attrValue=attribs[ai].value,"data-mce-html"!==attrName?attrName.indexOf("data-mce")!==-1&&attrName.indexOf("data-mce-p-")===-1||("img"!==targetNode.name||htmlSchema.isValid("img",attrName)&&"src"!=attrName||(attrName="data-mce-p-"+attrName),0===attrName.indexOf("on")&&editor.settings.allow_event_attributes&&(attrName="data-mce-p-"+attrName),attrName.indexOf("-")===-1?htmlSchema.isValid(targetNode.name,attrName)&&targetNode.attr(attrName,attrValue):targetNode.attr(attrName,attrValue)):targetNode.attr(attrName,attrValue);width&&(style.width=/^[0-9.]+$/.test(width)?width+"px":width),height&&(style.height=/^[0-9.]+$/.test(height)?height+"px":height);var classes=[];sourceNode.attr("class")&&(classes=sourceNode.attr("class").replace(/mce-(\S+)/g,"").replace(/\s+/g," ").trim().split(" "));var props=lookup[sourceNode.attr("type")]||lookup[sourceNode.attr("classid")]||{name:sourceNode.name};if(classes.push("mce-object mce-object-"+props.name),"audio"==sourceNode.name){var agent=navigator.userAgent.match(/(Chrome|Safari|Gecko)/);agent&&classes.push("mce-object-agent-"+agent[0].toLowerCase())}if(targetNode.attr("class",tinymce.trim(classes.join(" "))),(styles=editor.dom.serializeStyle(style))&&targetNode.attr("style",styles),!sourceNode.attr("src")){var sources=sourceNode.getAll("source");if(sources.length){var node=sources[0],name="src";"img"===targetNode.name&&(name="data-mce-p-"+name),targetNode.attr(name,node.attr("src"))}}if("object"===sourceNode.name){if(!sourceNode.attr("data")){var params=sourceNode.getAll("param");each(params,function(param){if("src"===param.attr("name")||"url"===param.attr("name"))return targetNode.attr({"data-mce-p-data":param.attr("value")}),!1})}targetNode.attr("data-mce-p-type",props.type)}sourceNode.firstChild&&(innerHtml=new tinymce.html.Serializer({inner:!0}).serialize(sourceNode)),innerHtml&&(targetNode.attr("data-mce-html",escape(sanitize(editor,innerHtml))),targetNode.empty())},isWithinEmbed=function(node){for(;node=node.parent;)if(node.attr("data-mce-object"))return!0;return!1},placeHolderConverter=function(editor){var invalid_elements=editor.settings.invalid_elements.split(",");return function(nodes){for(var node,i=nodes.length;i--;)node=nodes[i],node.parent&&(node.parent.attr("data-mce-object")||("iframe"===node.name&&validateIframe(editor,node)===!1&&invalid_elements.push("iframe"),tinymce.inArray(invalid_elements,node.name)===-1?editor.settings.media_live_embed&&!isObjectEmbed(node.name)?isWithinEmbed(node)||node.replace(createPreviewNode(editor,node)):isWithinEmbed(node)||node.replace(createPlaceholderNode(editor,node)):node.remove()))}},getMediaData=function(ed){var mediatype,data={},node=ed.dom.getParent(ed.selection.getNode(),"[data-mce-object]");if(!node)return data;node.className.indexOf("mce-object-preview")!==-1&&(node=node.firstChild),mediatype=node.getAttribute("data-mce-object")||node.nodeName.toLowerCase();var html=ed.dom.getAttrib(node,"data-mce-html");html&&extend(data,htmlToData(ed,mediatype,html));var i,attribs=node.attributes;for(data.src=ed.dom.getAttrib(node,"data-mce-p-src")||ed.dom.getAttrib(node,"data-mce-p-data")||ed.dom.getAttrib(node,"src"),data.src=ed.convertURL(data.src),i=attribs.length-1;i>=0;i--){var value,attrib=attribs.item(i),name=attrib.name;value=ed.dom.getAttrib(node,name),name.indexOf("data-mce-p-")!==-1&&(name=name.substr(11)),"data"!==name&&"src"!==name&&"type"!==name&&"codebase"!==name&&"classid"!==name&&("poster"===name&&(value=ed.convertURL(value)),"flashvars"===name&&(value=decodeURIComponent(value)),name.indexOf("data-mce-")===-1&&(data[name]=value))}return data},updateMedia=function(ed,data){var preview,attribs={},node=ed.dom.getParent(ed.selection.getNode(),"[data-mce-object]"),nodeName=node.nodeName.toLowerCase();ed.dom.removeClass(node,"mce-object-preview-block"),node.className.indexOf("mce-object-preview")!==-1&&(nodeName=node.getAttribute("data-mce-object"),node=ed.dom.select(nodeName,node),preview=ed.dom.getParent(node,"[data-mce-object]")),each(data,function(value,name){return"html"===name&&value?(attribs["data-mce-html"]=escape(value),!0):"img"!==nodeName||htmlSchema.isValid(nodeName,name)&&"src"!==name?void(attribs[name]=value):(attribs["data-mce-p-"+name]=value,!0)}),ed.dom.setAttribs(node,attribs),each(["width","height"],function(key){attribs[key]&&(ed.dom.setStyle(node,key,attribs[key]),preview&&ed.dom.setStyle(preview,key,attribs[key]))})};tinymce.create("tinymce.plugins.MediaPlugin",{init:function(ed,url){function isMediaNode(node){return node&&isMediaObject(ed,node)}function updatePreviewSelection(ed){each(ed.dom.select(".mce-object-preview",ed.getBody()),function(node){!ed.dom.isBlock(node.parentNode)||node.previousSibling||node.nextSibling||ed.dom.insertAfter(ed.dom.create("br",{"data-mce-bogus":1}),node)})}var self=this;self.editor=ed,self.url=url,ed.onPreInit.add(function(){var invalid=ed.settings.invalid_elements;!ed.settings.forced_root_block,"html4"===ed.settings.schema&&(ed.schema.addValidElements("iframe[longdesc|name|src|frameborder|marginwidth|marginheight|scrolling|align|width|height|allowfullscreen|seamless|*]"),ed.schema.addValidElements("video[src|autobuffer|autoplay|loop|controls|width|height|poster|*],audio[src|autobuffer|autoplay|loop|controls|*],source[src|type|media|*],embed[src|type|width|height|*]")),invalid=tinymce.explode(invalid,","),ed.parser.addNodeFilter("iframe,video,audio,object,embed",placeHolderConverter(ed)),ed.serializer.addAttributeFilter("data-mce-object",function(nodes,name){for(var node,i=nodes.length;i--;)node=nodes[i],node.parent&&convertPlaceholderToMedia(ed,node)})}),ed.onInit.add(function(){var settings=ed.settings;ed.theme.onResolveName.add(function(theme,o){var node,name;if(node=ed.dom.getParent(o.node,"[data-mce-object]")){if(name=node.getAttribute("data-mce-object"),o.node!==node)return void(o.name="");if("IMG"!==node.nodeName){node=ed.dom.select("iframe,audio,video",node);var src=ed.dom.getAttrib(node,"src")||ed.dom.getAttrib(node,"data-mce-p-src")||"";if(src){var str=isSupportedMedia(ed,src)||"";str&&(name=str[0].toUpperCase()+str.slice(1))}}"object"===name&&(name="media"),o.name=name}}),ed.settings.compress.css||ed.dom.loadCSS(url+"/css/content.css"),ed.onObjectResized.add(function(ed,elm,width,height){isMediaNode(elm)&&(ed.dom.hasClass(elm,"mce-object-preview")&&(ed.dom.setStyles(elm,{width:"",height:""}),elm=elm.firstChild),ed.dom.setAttrib(elm,"data-mce-width",width),ed.dom.setAttrib(elm,"data-mce-height",height),ed.dom.removeAttrib(elm,"width"),ed.dom.removeAttrib(elm,"height"),ed.dom.setStyles(elm,{width:width,height:height}))}),ed.dom.bind(ed.getDoc(),"mousedown touchstart keydown",function(e){var node=ed.dom.getParent(e.target,".mce-object-preview");if(node){if(window.setTimeout(function(){node.setAttribute("data-mce-selected","2")},100),e.stopImmediatePropagation(),"mousedown"===e.type&&VK.metaKeyPressed(e))return previewToPlaceholder(ed,node)}else;}),ed.dom.bind(ed.getDoc(),"keyup click",function(e){var node=ed.selection.getNode();if(each(ed.dom.select(".mce-object-preview video, .mce-object-preview audio"),function(elm){elm.pause()}),node&&"IMG"===node.nodeName&&"object"!==node.getAttribute("data-mce-object")&&"click"===e.type&&VK.metaKeyPressed(e))return placeholderToPreview(ed,node)}),ed.onBeforeExecCommand.add(function(ed,cmd,ui,v,o){if(cmd&&cmd.indexOf("Format")!==-1){var node=ed.selection.getNode();isMediaNode(node)&&("IMG"!==node.nodeName&&(node=node.firstChild,ed.selection.select(node)),tinymce.is(v,"object")&&(v.node=node))}}),ed.selection.onBeforeSetContent.add(function(ed,o){settings.media_live_embed&&(o.content=o.content.replace(/<br data-mce-caret="1"[^>]+>/gi,""),/^<(iframe|video|audio)([^>]+)><\/(iframe|video|audio)>$/.test(o.content)&&(o.content+='<br data-mce-caret="1" />'))})}),ed.onKeyDown.add(function(ed,e){var node=ed.selection.getNode();e.keyCode!==VK.BACKSPACE&&e.keyCode!==VK.DELETE||node&&(node===ed.getBody()&&(node=e.target),isMediaNode(node)&&(node=ed.dom.getParent(node,"[data-mce-object]")||node,ed.dom.remove(node),ed.nodeChanged()))}),ed.onSetContent.add(function(ed,o){updatePreviewSelection(ed)}),tinymce.util.MediaEmbed={dataToHtml:function(name,data,innerHtml){var html="";return"iframe"!==name&&"video"!==name&&"audio"!==name||(html="string"==typeof data?data:ed.dom.createHTML(name,data,innerHtml)),html}},ed.addCommand("insertMediaHtml",function(ui,value){var data={},name="iframe",innerHtml="";"string"==typeof value?data=value:value.name&&value.data&&(name=value.name,data=value.data,innerHtml=value.innerHtml||"");var html=tinymce.util.MediaEmbed.dataToHtml(name,data,innerHtml);ed.execCommand("mceInsertContent",!1,html,{skip_undo:1}),updatePreviewSelection(ed),ed.undoManager.add()})},getMediaData:function(){return getMediaData(this.editor)},updateMedia:function(data){return updateMedia(this.editor,data)},isMediaObject:function(node){return isMediaObject(this.editor,node)}}),tinymce.PluginManager.add("media",tinymce.plugins.MediaPlugin)}();