/* jce - 2.9.33 | 2023-01-18 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2022 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){function findAndReplaceDOMText(regex,node,replacementNode,captureGroup,schema){function getMatchIndexes(m,captureGroup){if(captureGroup=captureGroup||0,!m[0])throw"findAndReplaceDOMText cannot handle zero-length matches";var index=m.index;if(captureGroup>0){var cg=m[captureGroup];if(!cg)throw"Invalid capture group";index+=m[0].indexOf(cg),m[0]=cg}return[index,index+m[0].length,[m[0]]]}function getText(node){var txt;if(3===node.nodeType)return node.data;if(hiddenTextElementsMap[node.nodeName]&&!blockElementsMap[node.nodeName])return"";if(txt="",(blockElementsMap[node.nodeName]||shortEndedElementsMap[node.nodeName])&&(txt+="\n"),node=node.firstChild)do txt+=getText(node);while(node=node.nextSibling);return txt}function stepThroughMatches(node,matches,replaceFn){var startNode,endNode,startNodeIndex,endNodeIndex,innerNodes=[],atIndex=0,curNode=node,matchLocation=matches.shift(),matchIndex=0;out:for(;;){if((blockElementsMap[curNode.nodeName]||shortEndedElementsMap[curNode.nodeName])&&atIndex++,3===curNode.nodeType&&(!endNode&&curNode.length+atIndex>=matchLocation[1]?(endNode=curNode,endNodeIndex=matchLocation[1]-atIndex):startNode&&innerNodes.push(curNode),!startNode&&curNode.length+atIndex>matchLocation[0]&&(startNode=curNode,startNodeIndex=matchLocation[0]-atIndex),atIndex+=curNode.length),startNode&&endNode){if(curNode=replaceFn({startNode:startNode,startNodeIndex:startNodeIndex,endNode:endNode,endNodeIndex:endNodeIndex,innerNodes:innerNodes,match:matchLocation[2],matchIndex:matchIndex}),atIndex-=endNode.length-endNodeIndex,startNode=null,endNode=null,innerNodes=[],matchLocation=matches.shift(),matchIndex++,!matchLocation)break}else{if((!hiddenTextElementsMap[curNode.nodeName]||blockElementsMap[curNode.nodeName])&&curNode.firstChild){curNode=curNode.firstChild;continue}if(curNode.nextSibling){curNode=curNode.nextSibling;continue}}for(;;){if(curNode.nextSibling){curNode=curNode.nextSibling;break}if(curNode.parentNode===node)break out;curNode=curNode.parentNode}}}function genReplacer(nodeName){var makeReplacementNode;if("function"!=typeof nodeName){var stencilNode=nodeName.nodeType?nodeName:doc.createElement(nodeName);makeReplacementNode=function(fill,matchIndex){var clone=stencilNode.cloneNode(!1);return clone.setAttribute("data-mce-index",matchIndex),fill&&clone.appendChild(doc.createTextNode(fill)),clone}}else makeReplacementNode=nodeName;return function(range){var before,after,parentNode,startNode=range.startNode,endNode=range.endNode,matchIndex=range.matchIndex;if(startNode===endNode){var node=startNode;parentNode=node.parentNode,range.startNodeIndex>0&&(before=doc.createTextNode(node.data.substring(0,range.startNodeIndex)),parentNode.insertBefore(before,node));var el=makeReplacementNode(range.match[0],matchIndex);return parentNode.insertBefore(el,node),range.endNodeIndex<node.length&&(after=doc.createTextNode(node.data.substring(range.endNodeIndex)),parentNode.insertBefore(after,node)),node.parentNode.removeChild(node),el}before=doc.createTextNode(startNode.data.substring(0,range.startNodeIndex)),after=doc.createTextNode(endNode.data.substring(range.endNodeIndex));for(var elA=makeReplacementNode(startNode.data.substring(range.startNodeIndex),matchIndex),innerEls=[],i=0,l=range.innerNodes.length;i<l;++i){var innerNode=range.innerNodes[i],innerEl=makeReplacementNode(innerNode.data,matchIndex);innerNode.parentNode.replaceChild(innerEl,innerNode),innerEls.push(innerEl)}var elB=makeReplacementNode(endNode.data.substring(0,range.endNodeIndex),matchIndex);return parentNode=startNode.parentNode,parentNode.insertBefore(before,startNode),parentNode.insertBefore(elA,startNode),parentNode.removeChild(startNode),parentNode=endNode.parentNode,parentNode.insertBefore(elB,endNode),parentNode.insertBefore(after,endNode),parentNode.removeChild(endNode),elB}}var m,text,doc,blockElementsMap,hiddenTextElementsMap,shortEndedElementsMap,matches=[],count=0;if(doc=node.ownerDocument,blockElementsMap=schema.getBlockElements(),hiddenTextElementsMap=schema.getWhiteSpaceElements(),shortEndedElementsMap=schema.getShortEndedElements(),text=getText(node)){if(regex.global)for(;m=regex.exec(text);)matches.push(getMatchIndexes(m,captureGroup));else m=text.match(regex),matches.push(getMatchIndexes(m,captureGroup));return matches.length&&(count=matches.length,stepThroughMatches(node,matches,genReplacer(replacementNode))),count}}var DOM=tinymce.DOM;tinymce.create("tinymce.plugins.SearchReplacePlugin",{init:function(editor,url){function notFoundAlert(){editor.windowManager.alert(editor.getLang("searchreplace_dlg.notfound","The search has been completed. The search string could not be found."))}function updateButtonStates(){editor.updateSearchButtonStates.dispatch({next:!findSpansByIndex(currentIndex+1).length,prev:!findSpansByIndex(currentIndex-1).length})}function resetButtonStates(){editor.updateSearchButtonStates.dispatch({replace:!0,replaceAll:!0,next:!0,prev:!0})}function getElmIndex(elm){var value=elm.getAttribute("data-mce-index");return"number"==typeof value?""+value:value}function markAllMatches(regex){var node,marker;return marker=editor.dom.create("span",{"data-mce-bogus":1}),marker.className="mce-match-marker",node=editor.getBody(),self.done(!1),findAndReplaceDOMText(regex,node,marker,!1,editor.schema)}function unwrap(node){var parentNode=node.parentNode;node.firstChild&&parentNode.insertBefore(node.firstChild,node),node.parentNode.removeChild(node)}function findSpansByIndex(index){var nodes,spans=[];if(nodes=tinymce.toArray(editor.getBody().getElementsByTagName("span")),nodes.length)for(var i=0;i<nodes.length;i++){var nodeIndex=getElmIndex(nodes[i]);null!==nodeIndex&&nodeIndex.length&&nodeIndex===index.toString()&&spans.push(nodes[i])}return spans}function moveSelection(forward){var testIndex=currentIndex,dom=editor.dom;forward=forward!==!1,forward?testIndex++:testIndex--,dom.removeClass(findSpansByIndex(currentIndex),"mce-match-marker-selected");var spans=findSpansByIndex(testIndex);return spans.length?(dom.addClass(findSpansByIndex(testIndex),"mce-match-marker-selected"),editor.selection.scrollIntoView(spans[0]),testIndex):-1}function removeNode(node){var dom=editor.dom,parent=node.parentNode;dom.remove(node),dom.isEmpty(parent)&&dom.remove(parent)}function isMatchSpan(node){var matchIndex=getElmIndex(node);return null!==matchIndex&&matchIndex.length>0}function isMatchSpan(node){var matchIndex=getElmIndex(node);return null!==matchIndex&&matchIndex.length>0}var last,self=this,currentIndex=-1;editor.updateSearchButtonStates=new tinymce.util.Dispatcher(this),editor.addCommand("mceSearchReplace",function(){last={};var html='<div class="mceForm"><div class="mceModalRow">   <label for="'+editor.id+'_search_string">'+editor.getLang("searchreplace.findwhat","Search")+'</label>   <div class="mceModalControl">       <input type="text" id="'+editor.id+'_search_string" />   </div>   <div class="mceModalControl mceModalFlexNone">       <button class="mceButton" id="'+editor.id+'_search_prev" title="'+editor.getLang("searchreplace.prev","Previous")+'" disabled><i class="mceIcon mce_arrow-up"></i></button>       <button class="mceButton" id="'+editor.id+'_search_next" title="'+editor.getLang("searchreplace.next","Next")+'" disabled><i class="mceIcon mce_arrow-down"></i></button>   </div></div><div class="mceModalRow">   <label for="'+editor.id+'_replace_string">'+editor.getLang("searchreplace.replacewith","Replace")+'</label>   <div class="mceModalControl">       <input type="text" id="'+editor.id+'_replace_string" />   </div></div><div class="mceModalRow">   <div class="mceModalControl">       <input id="'+editor.id+'_matchcase" type="checkbox" />       <label for="'+editor.id+'_matchcase">'+editor.getLang("searchreplace.mcase","Match Case")+'</label>   </div>   <div class="mceModalControl">       <input id="'+editor.id+'_wholewords" type="checkbox" />       <label for="'+editor.id+'_wholewords">'+editor.getLang("searchreplace.wholewords","Whole Words")+"</label>   </div></div></div>";editor.windowManager.open({title:editor.getLang("searchreplace.search_desc","Search and Replace"),content:html,size:"mce-modal-landscape-small",overlay:!1,open:function(){var id=this.id,search=DOM.get(editor.id+"_search_string");search.value=editor.selection.getContent({format:"text"}),DOM.bind(editor.id+"_search_next","click",function(e){e.preventDefault(),editor.execCommand("mceSearchNext",!1)}),DOM.bind(editor.id+"_search_prev","click",function(e){e.preventDefault(),editor.execCommand("mceSearchPrev",!1)}),window.setTimeout(function(){search.focus()},10),editor.updateSearchButtonStates.add(function(obj){tinymce.each(obj,function(val,key){var elm=DOM.get(editor.id+"_search_"+key)||DOM.get(id+"_search_"+key);elm&&(elm.disabled=!!val)})})},close:function(){DOM.unbind(editor.id+"_search_next","click"),DOM.unbind(editor.id+"_search_prev","click"),editor.execCommand("mceSearchDone",!1)},buttons:[{title:editor.getLang("searchreplace.find","Find"),id:"find",onclick:function(e){e.preventDefault();var matchcase=DOM.get(editor.id+"_matchcase"),wholeword=DOM.get(editor.id+"_wholewords"),text=DOM.getValue(editor.id+"_search_string");editor.execCommand("mceSearch",!1,{textcase:!!matchcase.checked,text:text,wholeword:!!wholeword.checked})},classes:"primary"},{title:editor.getLang("searchreplace.replace","Replace"),id:"search_replace",onclick:function(e){e.preventDefault();var value=DOM.getValue(editor.id+"_replace_string");editor.execCommand("mceReplace",!1,value)}},{title:editor.getLang("searchreplace.replaceall","Replace All"),id:"search_replaceall",onclick:function(e){e.preventDefault();var value=DOM.getValue(editor.id+"_replace_string");editor.execCommand("mceReplaceAll",!1,value)}}]})}),editor.addCommand("mceSearch",function(ui,e){var count,text=e.text,caseState=e.textcase,wholeWord=e.wholeword;return text.length?last.text==text&&last.caseState==caseState&&last.wholeWord==wholeWord?0===findSpansByIndex(currentIndex+1).length?void notFoundAlert():(self.next(),void updateButtonStates()):(count=self.find(text,caseState,wholeWord),count||notFoundAlert(),updateButtonStates(),editor.updateSearchButtonStates.dispatch({replace:!count,replaceAll:!count}),void(last={text:text,caseState:caseState,wholeWord:wholeWord})):(self.done(!1),void resetButtonStates())}),editor.addCommand("mceSearchNext",function(){self.next(),updateButtonStates()}),editor.addCommand("mceSearchPrev",function(){self.prev(),updateButtonStates()}),editor.addCommand("mceReplace",function(ui,text){self.replace(text)||(resetButtonStates(),currentIndex=-1,last={})}),editor.addCommand("mceReplaceAll",function(ui,text){self.replace(text,!0,!0)||(resetButtonStates(),last={})}),editor.addCommand("mceSearchDone",function(){self.done()}),editor.addButton("search",{title:"searchreplace.search_desc",cmd:"mceSearchReplace"}),editor.addShortcut("meta+f","searchreplace.search_desc",function(){return editor.execCommand("mceSearchReplace")}),self.find=function(text,matchCase,wholeWord){text=text.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&"),text=wholeWord?"\\b"+text+"\\b":text;var count=markAllMatches(new RegExp(text,matchCase?"g":"gi"));return count&&(currentIndex=-1,currentIndex=moveSelection(!0)),count},self.next=function(){var index=moveSelection(!0);index!==-1&&(currentIndex=index)},self.prev=function(){var index=moveSelection(!1);index!==-1&&(currentIndex=index)},self.replace=function(text,forward,all){var i,nodes,node,matchIndex,currentMatchIndex,hasMore,nextIndex=currentIndex;for(forward=forward!==!1,node=editor.getBody(),nodes=tinymce.grep(tinymce.toArray(node.getElementsByTagName("span")),isMatchSpan),nodes=tinymce.grep(nodes,function(node){var parent=editor.dom.getParent(node,"[contenteditable]");return(!parent||"false"!==parent.contentEditable)&&"false"!==node.contentEditable}),i=0;i<nodes.length;i++){var nodeIndex=getElmIndex(nodes[i]);if(matchIndex=currentMatchIndex=parseInt(nodeIndex,10),all||matchIndex===currentIndex){for(text.length?(nodes[i].firstChild.nodeValue=text,unwrap(nodes[i])):removeNode(nodes[i]);nodes[++i];){if(matchIndex=parseInt(getElmIndex(nodes[i]),10),matchIndex!==currentMatchIndex){i--;break}removeNode(nodes[i])}forward&&nextIndex--}else currentMatchIndex>currentIndex&&nodes[i].setAttribute("data-mce-index",currentMatchIndex-1)}return editor.undoManager.add(),currentIndex=nextIndex,forward?(hasMore=findSpansByIndex(nextIndex+1).length>0,self.next()):(hasMore=findSpansByIndex(nextIndex-1).length>0,self.prev()),!all&&hasMore},self.done=function(keepEditorSelection){var i,nodes,startContainer,endContainer;for(nodes=tinymce.toArray(editor.getBody().getElementsByTagName("span")),i=0;i<nodes.length;i++){var nodeIndex=getElmIndex(nodes[i]);null!==nodeIndex&&nodeIndex.length&&(nodeIndex===currentIndex.toString()&&(startContainer||(startContainer=nodes[i].firstChild),endContainer=nodes[i].firstChild),unwrap(nodes[i]))}if(startContainer&&endContainer){var rng=editor.dom.createRng();return rng.setStart(startContainer,0),rng.setEnd(endContainer,endContainer.data.length),keepEditorSelection!==!1&&editor.selection.setRng(rng),rng}}}}),tinymce.PluginManager.add("searchreplace",tinymce.plugins.SearchReplacePlugin)}();