/* jce - 2.9.7 | 2021-05-13 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){function createTextNode(value,raw){var text=new Node("#text",3);return text.raw=raw!==!1,text.value=value,text}function isOnlyChild(node){var parent=node.parent,child=parent.firstChild,count=0;if(child)do{if(1===child.type){if(child.attributes.map["data-mce-type"]||child.attributes.map["data-mce-bogus"])continue;if(child===node)continue;count++}8===child.type&&count++,3!==child.type||/^[ \t\r\n]*$/.test(child.value)||count++}while(child=child.next);return 0===count}var each=tinymce.each,Node=tinymce.html.Node,VK=tinymce.VK,DomParser=tinymce.html.DomParser,Serializer=tinymce.html.Serializer,SaxParser=tinymce.html.SaxParser;tinymce.create("tinymce.plugins.CodePlugin",{init:function(ed,url){function processOnInsert(value,node){if(/\{.+\}/gi.test(value)&&ed.settings.code_protect_shortcode){var tagName;node&&ed.dom.isEmpty(node)&&(tagName="pre"),value=processShortcode(value,tagName)}return ed.settings.code_allow_custom_xml&&(value=processXML(value)),/<(\?|script|style)/.test(value)&&(value=value.replace(/<(script|style)([^>]*?)>([\s\S]*?)<\/\1>/gi,function(match,type){return ed.getParam("code_allow_"+type)?(match=match.replace(/<br[^>]*?>/gi,"\n"),createCodePre(match,type)):""}),value=processPhp(value)),value}function processShortcode(html,tagName){return html.indexOf("{")===-1?html:"{"==html.charAt(0)&&html.length<3?html:(tagName=tagName||"span",html.replace(/(?:([a-z0-9]>)?)(?:\{)([\/\w-]+)(.*)(?:\})(?:(.*)(?:\{\/\1\}))?/g,function(match){return">"===match.charAt(1)?match:createShortcodePre(match,tagName)}))}function processPhp(content){return ed.settings.code_allow_php?(content=content.replace(/\="([^"]+?)"/g,function(a,b){return b=b.replace(/<\?(php)?(.+?)\?>/gi,function(x,y,z){return"[php:start]"+ed.dom.encode(z)+"[php:end]"}),'="'+b+'"'}),/<textarea/.test(content)&&(content=content.replace(/<textarea([^>]*)>([\s\S]*?)<\/textarea>/gi,function(a,b,c){return c=c.replace(/<\?(php)?(.+?)\?>/gi,function(x,y,z){return"[php:start]"+ed.dom.encode(z)+"[php:end]"}),"<textarea"+b+">"+c+"</textarea>"})),content=content.replace(/<([^>]+)<\?(php)?(.+?)\?>([^>]*?)>/gi,function(a,b,c,d,e){return" "!==b.charAt(b.length)&&(b+=" "),"<"+b+'data-mce-php="'+d+'" '+e+">"}),content=content.replace(/<\?(php)?([\s\S]+?)\?>/gi,function(match){return match=match.replace(/\n/g,"<br />"),createCodePre(match,"php","span")})):content.replace(/<\?(php)?([\s\S]*?)\?>/gi,"")}function isInvalidElement(name){var invalid_elements=ed.settings.invalid_elements.split(",");return tinymce.inArray(invalid_elements,name)!==-1}function isXmlElement(name){return!htmlSchema.isValid(name)&&!isInvalidElement(name)}function validateXml(xml){function isValid(tag,attr){return!!isXmlElement(tag)||ed.schema.isValid(tag,attr)}var html=[];return new SaxParser({start:function(name,attrs,empty){if(isValid(name)){if(html.push("<",name),attrs)for(i=0,l=attrs.length;i<l;i++)attr=attrs[i],isValid(name,attr.name)&&(ed.settings.allow_event_attributes!==!0&&0===attr.name.indexOf("on")||html.push(" ",attr.name,'="',ed.dom.encode(""+attr.value,!0),'"'));empty?html[html.length]=" />":html[html.length]=">"}},text:function(value){value.length>0&&(html[html.length]=value)},end:function(name){isValid(name)&&html.push("</",name,">")},cdata:function(text){html.push("<![CDATA[",text,"]]>")},comment:function(text){html.push("<!--",text,"-->")}},xmlSchema).parse(xml),html.join("")}function processXML(content){return content.replace(/<([a-z0-9\-_\:\.]+)(?:[^>]*?)\/?>((?:[\s\S]*?)<\/\1>)?/gi,function(match,tag){return"svg"===tag&&ed.settings.code_allow_svg_in_xml===!1?match:"math"===tag&&ed.settings.code_allow_mathml_in_xml===!1?match:isXmlElement(tag)?(ed.settings.code_validate_xml!==!1&&(match=validateXml(match)),createCodePre(match,"xml")):match})}function createShortcodePre(data,tag){return ed.dom.createHTML(tag||"pre",{"data-mce-code":"shortcode","data-mce-type":"code"},ed.dom.encode(data))}function createCodePre(data,type,tag){return code_blocks===!1?(data=data.replace(/<br[^>]*?>/gi,"\n"),ed.dom.createHTML("img",{src:"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7","data-mce-resize":"false","data-mce-code":type||"script","data-mce-type":"placeholder","data-mce-value":escape(data)})):ed.dom.createHTML(tag||"pre",{"data-mce-code":type||"script","data-mce-type":"code"},ed.dom.encode(data))}function handleEnterInPre(ed,node,before){var parents=ed.dom.getParents(node,blockElements.join(",")),newBlockName=ed.settings.forced_root_block||"p";ed.settings.force_block_newlines===!1&&(newBlockName="br");var block=parents.shift();if(block!==ed.getBody()){var elm=ed.dom.create(newBlockName,{}," ");before?block.parentNode.insertBefore(elm,block):ed.dom.insertAfter(elm,block);var rng=ed.selection.getRng();rng.setStart(elm,0),rng.setEnd(elm,0),ed.selection.setRng(rng),ed.selection.scrollIntoView(elm)}}this.editor=ed,this.url=url;var blockElements=[],htmlSchema=new tinymce.html.Schema({schema:"mixed",invalid_elements:ed.settings.invalid_elements}),xmlSchema=new tinymce.html.Schema({verify_html:!1}),code_blocks=ed.settings.code_use_blocks!==!1;ed.settings.code_allow_script&&(ed.settings.allow_script_urls=!0),ed.addCommand("InsertShortCode",function(ui,html){return ed.settings.code_protect_shortcode&&(html=processShortcode(html,"pre",!0),tinymce.is(html)&&ed.execCommand("mceReplaceContent",!1,html)),!1}),ed.onKeyDown.add(function(ed,e){if(e.keyCode==VK.ENTER){var node=ed.selection.getNode();if("PRE"===node.nodeName){var type=node.getAttribute("data-mce-code")||"";if(type){if("shortcode"===type)return e.shiftKey?ed.execCommand("InsertLineBreak",!1,e):handleEnterInPre(ed,node),void e.preventDefault();e.altKey||e.shiftKey?handleEnterInPre(ed,node):ed.execCommand("InsertLineBreak",!1,e),e.preventDefault()}}"SPAN"===node.nodeName&&node.getAttribute("data-mce-code")&&(handleEnterInPre(ed,node),e.preventDefault())}if(e.keyCode==VK.UP&&e.altKey){var node=ed.selection.getNode();handleEnterInPre(ed,node,!0),e.preventDefault()}if(9==e.keyCode&&!VK.metaKeyPressed(e)){var node=ed.selection.getNode();"PRE"===node.nodeName&&node.getAttribute("data-mce-code")&&(ed.selection.setContent("\t",{no_events:!0}),e.preventDefault())}if(e.keyCode===VK.BACKSPACE||e.keyCode===VK.DELETE){var node=ed.selection.getNode();"SPAN"===node.nodeName&&node.getAttribute("data-mce-code")&&"placeholder"===node.getAttribute("data-mce-type")&&(ed.undoManager.add(),ed.dom.remove(node),e.preventDefault())}}),ed.onPreInit.add(function(){function isCodePlaceholder(node){return"SPAN"===node.nodeName&&node.getAttribute("data-mce-code")&&"placeholder"==node.getAttribute("data-mce-type")}ed.settings.content_css!==!1&&ed.dom.loadCSS(url+"/css/content.css"),ed.dom.bind(ed.getDoc(),"keyup click",function(e){var node=e.target,sel=ed.selection.getNode();return ed.dom.removeClass(ed.dom.select(".mce-item-selected"),"mce-item-selected"),node===ed.getBody()&&isCodePlaceholder(sel)?void(sel.parentNode!==node||sel.nextSibling||ed.dom.insertAfter(ed.dom.create("br",{"data-mce-bogus":1}),sel)):void(isCodePlaceholder(node)&&(e.preventDefault(),e.stopImmediatePropagation(),ed.selection.select(node),window.setTimeout(function(){ed.dom.addClass(node,"mce-item-selected")},10),e.preventDefault()))});var ctrl=ed.controlManager.get("formatselect");ctrl&&each(["script","style","php","shortcode","xml"],function(key){var title=ed.getLang("code."+key,key);return"shortcode"===key&&ed.settings.code_protect_shortcode?(ctrl.add(title,key,{class:"mce-code-"+key}),ed.formatter.register("shortcode",{block:"pre",attributes:{"data-mce-code":"shortcode"}}),!0):("xml"===key&&(ed.settings.code_allow_xml=!!ed.settings.code_allow_custom_xml),void(ed.getParam("code_allow_"+key)&&code_blocks&&(ctrl.add(title,key,{class:"mce-code-"+key}),ed.formatter.register(key,{block:"pre",attributes:{"data-mce-code":key},onformat:function(elm,fmt,vars){each(ed.dom.select("br",elm),function(br){ed.dom.replace(ed.dom.doc.createTextNode("\n"),br)})}}))))}),each(ed.schema.getBlockElements(),function(block,blockName){blockElements.push(blockName)}),ed.plugins.textpattern&&ed.settings.code_protect_shortcode&&(ed.plugins.textpattern.addPattern({start:"{",end:"}",cmd:"InsertShortCode",remove:!0}),ed.plugins.textpattern.addPattern({start:" {",end:"}",format:"inline-shortcode",remove:!1})),ed.formatter.register("inline-shortcode",{inline:"span",attributes:{"data-mce-code":"shortcode"}}),ed.selection.onBeforeSetContent.add(function(sel,o){ed.settings.code_protect_shortcode&&o.content.indexOf('data-mce-code="shortcode"')===-1&&(o.content=processShortcode(o.content))}),ed.selection.onSetContent.add(function(sel,o){each(ed.dom.select("pre[data-mce-code]",ed.getBody()),function(elm){var p=ed.dom.getParent(elm,"p");p&&1===p.childNodes.length&&ed.dom.remove(p,1)})}),ed.parser.addNodeFilter("script,style,noscript",function(nodes){for(var node,i=nodes.length;i--;){var node=nodes[i],type=node.attr("type");if(type&&node.attr("type","mce-no/type"==type?null:type.replace(/^mce\-/,"")),node.firstChild&&(node.firstChild.value=node.firstChild.value.replace(/<span([^>]+)>([\s\S]+?)<\/span>/gi,function(match,attr,content){return attr.indexOf("data-mce-code")===-1?match:ed.dom.decode(content)})),code_blocks){var value=new Serializer({validate:!1}).serialize(node);value=tinymce.trim(value);var pre=new Node("pre",1);pre.attr({"data-mce-code":node.name});var text=createTextNode(value,!1);pre.append(text),node.replace(pre)}else{var value="";node.firstChild&&(value=tinymce.trim(node.firstChild.value));var placeholder=Node.create("img",{src:"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7","data-mce-code":node.name,"data-mce-type":"placeholder","data-mce-resize":"false",title:ed.dom.encode(value)});each(node.attributes,function(attr){placeholder.attr("data-mce-p-"+attr.name,attr.value)}),value&&placeholder.attr("data-mce-value",escape(value)),node.replace(placeholder)}}}),ed.parser.addAttributeFilter("data-mce-code",function(nodes,name){function isBody(parent){return"body"===parent.name}function isValidCode(type){return"shortcode"===type||"php"===type}for(var node,parent,i=nodes.length;i--;)if(node=nodes[i],parent=node.parent,"placeholder"!=node.attr("data-mce-type")&&isValidCode(node.attr(name))){var value=node.firstChild.value;if(value&&(node.firstChild.value=value.replace(/<br[\s\/]*>/g,"\n")),parent){if(parent.attr(name)){node.unwrap();continue}if(isBody(parent)||isOnlyChild(node))node.name="pre";else if("span"==node.name&&node===parent.lastChild){var nbsp=createTextNode(" ");parent.append(nbsp)}}}}),ed.serializer.addAttributeFilter("data-mce-code",function(nodes,name){function isXmlNode(node){return!/(shortcode|php)/.test(node.attr("data-mce-code"))}for(var node,child,i=nodes.length;i--;){node=nodes[i],root_block=!1;var type=node.attr(name);if("img"!==node.name){if(node.isEmpty()&&node.remove(),"xml"!==type){"script"!==type&&"style"!==type||(root_block=type);var child=node.firstChild,newNode=node.clone(!0),text="";if(child)do if(isXmlNode(node)){var value="br"==child.name?"\n":child.value;value&&(text+=value)}while(child=child.next);if(text){newNode.empty();var parser=new DomParser({validate:!1});"script"!==type&&"style"!==type||parser.addNodeFilter(type,function(items){for(var n=items.length;n--;)each(items[n].attributes,function(attr,i){return!attr||void(ed.schema.isValid(type,attr.name)===!1&&items[n].attr(attr.name,null))})});var fragment=parser.parse(text,{forced_root_block:root_block});newNode.append(fragment)}if(node.replace(newNode),"shortcode"===type&&"pre"===newNode.name){var newline=createTextNode("\n");newNode.append(newline),newNode.unwrap()}}}else{var elm=new Node(type,1);for(var key in node.attributes.map){var value=node.attributes.map[key];key.indexOf("data-mce-p-")!==-1?key=key.substr(11):value=null,elm.attr(key,value)}var value=node.attr("data-mce-value");if(value){var text=createTextNode("\n"+unescape(value)+"\n");elm.append(text)}node.replace(elm)}}}),ed.plugins.clipboard&&ed.onGetClipboardContent.add(function(ed,content){var value,text=content["text/plain"]||"";if(text=tinymce.trim(text)){var node=ed.selection.getNode();if(node&&"PRE"===node.nodeName)return;value=processOnInsert(text,node),value!==text&&(content["text/plain"]="",content["text/html"]=content["x-tinymce/html"]=value)}})}),ed.onInit.add(function(){ed.theme&&ed.theme.onResolveName&&ed.theme.onResolveName.add(function(theme,o){var node=o.node;node.getAttribute("data-mce-code")&&(o.name=node.getAttribute("data-mce-code"))})}),ed.onBeforeSetContent.add(function(ed,o){ed.settings.code_protect_shortcode&&o.content&&o.load&&(o.content=processShortcode(o.content)),ed.settings.code_allow_custom_xml&&o.content&&o.load&&(o.content=processXML(o.content)),/<(\?|script|style)/.test(o.content)&&(ed.settings.code_allow_script||(o.content=o.content.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi,"")),ed.settings.code_allow_style||(o.content=o.content.replace(/<style[^>]*>([\s\S]*?)<\/style>/gi,"")),o.content=processPhp(o.content))}),ed.onPostProcess.add(function(ed,o){o.get&&(/(data-mce-php|\[php:start\])/.test(o.content)&&(o.content=o.content.replace(/\[php:\s?start\]([^\[]]+)\[php:\s?end\]/g,function(a,b){return"<?php"+ed.dom.decode(b)+"?>"}),o.content=o.content.replace(/<textarea([^>]*)>([\s\S]*?)<\/textarea>/gi,function(a,b,c){return/&lt;\?php/.test(c)&&(c=ed.dom.decode(c)),"<textarea"+b+">"+c+"</textarea>"}),o.content=o.content.replace(/data-mce-php="([^"]+?)"/g,function(a,b){return"<?php"+ed.dom.decode(b)+"?>"})),ed.settings.code_protect_shortcode&&(o.content=o.content.replace(/\{(.*)\}/gi,function(match,content){return"{"+ed.dom.decode(content)+"}"})),o.content=o.content.replace(/<(pre|span)([^>]+?)>([\s\S]*?)<\/\1>/gi,function(match,tag,attr,content){return attr.indexOf("data-mce-code")===-1?match:(content=tinymce.trim(content),content=ed.dom.decode(content),content=content.replace(/<br[^>]*?>/gi,"\n"),attr.indexOf('data-mce-code="php"')!==-1&&(content=content.replace(/<\?(php)?/gi,"").replace(/\?>/g,""),content="<?php\n"+tinymce.trim(content)+"\n?>"),content)}),o.content=o.content.replace(/<!--mce:protected ([\s\S]+?)-->/gi,function(match,content){return unescape(content)}))})}}),tinymce.PluginManager.add("code",tinymce.plugins.CodePlugin)}();