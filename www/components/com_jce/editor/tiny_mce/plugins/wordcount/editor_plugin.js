/* jce - 2.9.18 | 2021-12-09 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){var VK=tinymce.VK,DOM=tinymce.DOM;tinymce.PluginManager.add("wordcount",function(ed,url){function getCount(){var tc=0,tx=ed.getContent({format:"raw"});if(tx){tx=tx.replace(/\.\.\./g," "),tx=tx.replace(/<.[^<>]*?>/g," ").replace(/&nbsp;|&#160;/gi," "),tx=tx.replace(/(\w+)(&#?[a-z0-9]+;)+(\w+)/i,"$1$3").replace(/&.+?;/g," "),tx=tx.replace(cleanre,"");var wordArray=tx.match(countre);wordArray&&(tc=wordArray.length)}return tc}function countChars(ed){var limit=parseInt(ed.getParam("wordcount_limit",0),10),showAlert=ed.getParam("wordcount_alert",0);block||(block=1,setTimeout(function(){if(!ed.destroyed){var tc=getCount(ed);limit&&(tc=limit-tc,tc<0?(DOM.addClass(target_id,"mceWordCountLimit"),showAlert&&ed.windowManager.alert(ed.getLang("wordcount.limit_alert","You have reached the word limit set for this content."))):DOM.removeClass(target_id,"mceWordCountLimit")),DOM.setHTML(target_id,tc.toString()),ed.onWordCount.dispatch(ed,tc),setTimeout(function(){block=0},update_rate)}},1))}function checkKeys(key){return key!==last&&(key===VK.ENTER||last===VK.SPACEBAR||checkDelOrBksp(last))}function checkDelOrBksp(key){return key===VK.DELETE||key===VK.BACKSPACE}var self=this,last=0,block=0,countre=ed.getParam("wordcount_countregex",/[\w\u2019\x27\-\u00C0-\u1FFF]+/g),cleanre=ed.getParam("wordcount_cleanregex",/[0-9.(),;:!?%#$?\x27\x22_+=\\\/\-]*/g),update_rate=ed.getParam("wordcount_update_rate",2e3),update_on_delete=ed.getParam("wordcount_update_on_delete",!1),target_id=ed.id+"_word_count";ed.onWordCount=new tinymce.util.Dispatcher(self),ed.onPostRender.add(function(ed,cm){if(target_id=ed.getParam("wordcount_target_id",target_id),!DOM.get(target_id)){var row=DOM.get(ed.id+"_path_row");row&&DOM.add(row.parentNode,"div",{class:"mceWordCount"},ed.getLang("wordcount.words","Words: ")+'<span id="'+target_id+'" class="mceText">0</span>')}}),ed.onInit.add(function(ed){ed.selection.onSetContent.add(function(){countChars(ed)}),countChars(ed)}),ed.onSetContent.add(function(ed){countChars(ed)}),ed.onKeyUp.add(function(ed,e){(checkKeys(e.keyCode)||update_on_delete&&checkDelOrBksp(e.keyCode))&&countChars(ed),last=e.keyCode})})}();