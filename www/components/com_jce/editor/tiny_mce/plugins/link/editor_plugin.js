/* jce - 2.9.11 | 2021-08-17 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){function createLink(ed,data){var node=ed.selection.getNode(),anchor=ed.dom.getParent(node,"a[href]");if("string"==typeof data&&(data={url:data,text:data}),!data.url)return isAnchor(node)&&ed.execCommand("unlink",!1),!1;var text=getAnchorText(ed.selection,isAnchor(node)?node:null)||"";data.text=data.text||text||data.url,/^\s*www\./i.test(data.url)&&(data.url="https://"+data.url);var args={href:data.url};ed.settings.default_link_target&&(args.target=ed.settings.default_link_target),ed.selection.isCollapsed()?ed.execCommand("mceInsertContent",!1,ed.dom.createHTML("a",args,data.text)):(ed.execCommand("mceInsertLink",!1,args),isAnchor(anchor)&&updateTextContent(anchor,data.text)),ed.undoManager.add(),ed.nodeChanged()}var DOM=tinymce.DOM,Event=tinymce.dom.Event,isAnchor=function(elm){return elm&&"a"===elm.nodeName.toLowerCase()},hasFileSpan=function(elm){return isAnchor(elm)&&elm.querySelector("span.wf_file_text")&&1===elm.childNodes.length},collectNodesInRange=function(rng,predicate){if(rng.collapsed)return[];var contents=rng.cloneContents(),walker=new tinymce.dom.TreeWalker(contents.firstChild,contents),elements=[],current=contents.firstChild;do predicate(current)&&elements.push(current);while(current=walker.next());return elements},isOnlyTextSelected=function(ed){var inlineTextElements=ed.schema.getTextInlineElements(),isElement=function(elm){return 1===elm.nodeType&&!isAnchor(elm)&&!inlineTextElements[elm.nodeName.toLowerCase()]},elements=collectNodesInRange(ed.selection.getRng(),isElement);return 0===elements.length},trimCaretContainers=function(text){return text.replace(/\uFEFF/g,"")},getAnchorText=function(selection,anchorElm){var text=anchorElm?anchorElm.innerText||anchorElm.textContent:selection.getContent({format:"text"});return trimCaretContainers(text)},updateTextContent=function(elm,text){elm.firstChild&&1===elm.firstChild.nodeType&&(elm=elm.firstChild),"innerText"in elm?elm.innerText=text:elm.textContent=text};tinymce.create("tinymce.plugins.LinkPlugin",{init:function(ed,url){this.editor=ed,this.url=url,ed.addCommand("mceLink",function(){var se=ed.selection,n=se.getNode();"A"!=n.nodeName||isAnchor(n)||se.select(n),ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&task=plugin.display&plugin=link",size:"mce-modal-portrait-large"},{plugin_url:url})}),ed.addShortcut("meta+k","link.desc","mceLink");var urlCtrl,textCtrl;ed.onPreInit.add(function(){var params=ed.getParam("link",{});if(params.basic_dialog===!0){var cm=ed.controlManager,form=cm.createForm("link_form");urlCtrl=cm.createTextBox("link_url",{label:ed.getLang("url","URL"),name:"url",clear:!0,attributes:{required:!0}}),form.add(urlCtrl),textCtrl=cm.createTextBox("link_text",{label:ed.getLang("link.text","Text"),name:"text",clear:!0,attributes:{required:!0}}),form.add(textCtrl),ed.addCommand("mceLink",function(){ed.windowManager.open({title:ed.getLang("link.desc","Link"),items:[form],size:"mce-modal-landscape-small",open:function(){var label=ed.getLang("insert","Insert"),node=ed.selection.getNode(),src="",state=isOnlyTextSelected(ed);if(node=ed.dom.getParent(node,"a[href]")){if(ed.selection.select(node),src=ed.dom.getAttrib(node,"href"),src&&(label=ed.getLang("update","Update")),tinymce.isIE){var start=ed.selection.getStart(),end=ed.selection.getEnd();start===end&&"A"===start.nodeName&&(node=start)}hasFileSpan(node)&&(state=!0)}var text=getAnchorText(ed.selection,isAnchor(node)?node:null)||"";urlCtrl.value(src),textCtrl.value(text),textCtrl.setDisabled(!state),window.setTimeout(function(){urlCtrl.focus()},10),DOM.setHTML(this.id+"_insert",label)},buttons:[{title:ed.getLang("common.cancel","Cancel"),id:"cancel"},{title:ed.getLang("insert","Insert"),id:"insert",onsubmit:function(e){var data=form.submit();Event.cancel(e),createLink(ed,data)},classes:"primary",scope:self}]})})}}),ed.onInit.add(function(){ed&&ed.plugins.contextmenu&&ed.plugins.contextmenu.onContextMenu.add(function(th,m,e){m.addSeparator(),m.add({title:"link.desc",icon:"link",cmd:"mceLink",ui:!0}),"A"!=e.nodeName||ed.dom.getAttrib(e,"name")||m.add({title:"advanced.unlink_desc",icon:"unlink",cmd:"UnLink"})})}),ed.onNodeChange.add(function(ed,cm,n,co){var link=ed.dom.getParent(n,"a[href]"),anchor=link&&ed.dom.hasClass(link,"mce-item-anchor");ed.dom.removeAttrib(ed.dom.select("a"),"data-mce-selected"),link&&ed.dom.setAttrib(link,"data-mce-selected","inline-boundary"),cm.setActive("unlink",link),cm.setActive("link",link),cm.setDisabled("link",anchor)})},createControl:function(n,cm){var ed=this.editor;if("link"!==n)return null;var params=ed.getParam("link",{});if(params.quicklink===!1||params.basic_dialog===!0)return cm.createButton("link",{title:"link.desc",cmd:"mceLink"});var html='<div class="mceToolbarRow">   <div class="mceToolbarItem mceFlexAuto">       <input type="text" id="'+ed.id+'_link_input" aria-label="'+ed.getLang("dlg.url","URL")+'" />   </div>   <div class="mceToolbarItem">       <button type="button" id="'+ed.id+'_link_submit" class="mceButton mceButtonLink" title="'+ed.getLang("advanced.link_desc","Insert Link")+'" aria-label="'+ed.getLang("link.insert","Insert Link")+'">           <span class="mceIcon mce_link"></span>       </button>   </div>   <div class="mceToolbarItem">       <button type="button" id="'+ed.id+'_link_unlink" class="mceButton mceButtonUnlink" disabled="disabled" title="'+ed.getLang("advanced.unlink_desc","Remove Link")+'" aria-label="'+ed.getLang("advanced.unlink_desc","Remove Link")+'">           <span class="mceIcon mce_unlink"></span>       </button>   </div></div>',ctrl=cm.createSplitButton("link",{title:"link.desc",cmd:"mceLink",max_width:264,onselect:function(node){createLink(ed,{url:node.value,text:""})}});return ctrl?(ctrl.onRenderMenu.add(function(c,m){var item=m.add({onclick:function(e){e.preventDefault(),item.setSelected(!1);var n=ed.dom.getParent(e.target,".mceButton");if(!n.disabled){if(ed.dom.hasClass(n,"mceButtonLink")){var value=DOM.getValue(ed.id+"_link_input");createLink(ed,{url:value,text:""})}ed.dom.hasClass(n,"mceButtonUnlink")&&ed.execCommand("unlink",!1),m.hideMenu()}},html:html});m.onShowMenu.add(function(){var selection=ed.selection,value="";DOM.setAttrib(ed.id+"_link_unlink","disabled","disabled"),node=ed.dom.getParent(selection.getNode(),"a[href]"),isAnchor(node)&&(selection.select(node),value=node.getAttribute("href"),DOM.setAttrib(ed.id+"_link_unlink","disabled",null)),window.setTimeout(function(){DOM.get(ed.id+"_link_input").focus()},10),DOM.setValue(ed.id+"_link_input",value)})}),ctrl):void 0},isAnchor:isAnchor,hasFileSpan:hasFileSpan,isOnlyTextSelected:isOnlyTextSelected,getAnchorText:getAnchorText,updateTextContent:updateTextContent}),tinymce.PluginManager.add("link",tinymce.plugins.LinkPlugin)}();