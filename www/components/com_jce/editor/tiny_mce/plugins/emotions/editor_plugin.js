/* jce - 2.9.5 | 2021-04-07 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2021 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){var DOM=tinymce.DOM,each=(tinymce.dom.Event,tinymce.each),emoji=[{"😀":"grinning_face"},{"😁":"grinning_face_with_smiling_eyes"},{"😂":"face_with_tears_of_joy"},{"😃":"smiling_face_with_open_mouth"},{"😄":"smiling_face_with_open_mouth_and_smiling_eyes"},{"😅":"smiling_face_with_open_mouth_and_cold_sweat"},{"😆":"smiling_face_with_open_mouth_and_tightly_closed_eyes"},{"😇":"smiling_face_with_halo"},{"😈":"smiling_face_with_horns"},{"😉":"winking_face"},{"😊":"smiling_face_with_smiling_eyes"},{"😋":"face_savouring_delicious_food"},{"😌":"relieved_face"},{"😍":"smiling_face_with_heart_shaped_eyes"},{"😎":"smiling_face_with_sunglasses"},{"😏":"smirking_face"},{"😐":"neutral_face"},{"😑":"expressionless_face"},{"😒":"unamused_face"},{"😓":"face_with_cold_sweat"},{"😔":"pensive_face"},{"😕":"confused_face"},{"😖":"confounded_face"},{"😗":"kissing_face"},{"😘":"face_throwing_a_kiss"},{"😙":"kissing_face_with_smiling_eyes"},{"😚":"kissing_face_with_closed_eyes"},{"😛":"face_with_stuck_out_tongue"},{"😜":"face_with_stuck_out_tongue_and_winking_eye"},{"😝":"face_with_stuck_out_tongue_and_tightly_closed_eyes"},{"😞":"disappointed_face"},{"😟":"worried_face"},{"😠":"angry_face"},{"😡":"pouting_face"},{"😢":"crying_face"},{"😣":"persevering_face"},{"😤":"face_with_look_of_triumph"},{"😥":"disappointed_but_relieved_face"},{"😦":"frowning_face_with_open_mouth"},{"😧":"anguished_face"},{"😨":"fearful_face"},{"😩":"weary_face"},{"😪":"sleepy_face"},{"😫":"tired_face"},{"😬":"grimacing_face"},{"😭":"loudly_crying_face"},{"😮":"face_with_open_mouth"},{"😯":"hushed_face"},{"😰":"face_with_open_mouth_and_cold_sweat"},{"😱":"face_screaming_in_fear"},{"😲":"astonished_face"},{"😳":"flushed_face"},{"😴":"sleeping_face"},{"😵":"dizzy_face"},{"😶":"face_without_mouth"},{"😷":"face_with_medical_mask"},{"😸":"grinning_cat_face_with_smiling_eyes"},{"😹":"cat_face_with_tears_of_joy"},{"😺":"smiling_cat_face_with_open_mouth"},{"😻":"smiling_cat_face_with_heart_shaped_eyes"},{"😼":"cat_face_with_wry_smile"},{"😽":"kissing_cat_face_with_closed_eyes"},{"😾":"pouting_cat_face"},{"😿":"crying_cat_face"},{"🙀":"weary_cat_face"},{"🙁":"slightly_frowning_face"},{"🙂":"slightly_smiling_face"},{"🙃":"upside_down_face"},{"🙄":"face_with_rolling_eyes"},{"🙅":"face_with_no_good_gesture"},{"🙆":"face_with_ok_gesture"},{"🙇":"person_bowing_deeply"},{"🙈":"see_no_evil_monkey"},{"🙉":"hear_no_evil_monkey"},{"🙊":"speak_no_evil_monkey"},{"🙋":"happy_person_raising_one_hand"},{"🙌":"person_raising_both_hands_in_celebration"},{"🙍":"person_frowning"},{"🙎":"person_with_pouting_face"},{"🙏":"person_with_folded_hands"}];tinymce.create("tinymce.plugins.EmotionsPlugin",{init:function(ed,url){function createEmojiContent(icons,path){var content=document.createElement("div");return path&&path.indexOf("://")===-1&&(path=ed.documentBaseURI.toAbsolute(path,!0)),each(icons,function(data){if("string"==typeof data){var label="",src=data,item={};path&&(src=path+"/"+src),/\.(png|jpg|jpeg|gif)$/i.test(data)&&(label=data.replace(/\.[^.]+$/i,""),data='<img src="'+src+'" alt="'+ed.getLang("emotions."+label,label)+'" />'),item[data]=label,data=item}each(data,function(label,key){if(/\.(png|jpg|jpeg|gif)$/i.test(key)){var src=key;path&&(src=path+"/"+src),src=ed.documentBaseURI.toAbsolute(src,!0),key='<img src="'+src+'" alt="'+ed.getLang("emotions."+label,label)+'" />'}DOM.add(content,"button",{class:"mce_emotions_icon",title:ed.getLang("emotions."+label,label)},key)})}),content.innerHTML}var self=this;this.editor=ed,this.url=url,ed.addButton("emotions",{title:"emotions.desc",cmd:"mceEmotion"}),self.content="";var path=ed.getParam("emotions_url",url+"/img"),icons=ed.getParam("emotions_smilies",emoji,"hash");this.content=createEmojiContent(icons,path),this.loaded=!1,path&&/\.(json|txt)$/.test(path)&&!this.loaded&&(path.indexOf("://")===-1&&(path=ed.documentBaseURI.toAbsolute(path,!0)),this.loaded=!0,tinymce.util.XHR.send({url:path,success:function(text){try{icons=JSON.parse(text)}catch(e){}path=path.substring(0,path.lastIndexOf("/")),self.content=createEmojiContent(icons,path)}}))},createControl:function(n,cm){function insertEmoticon(n){var p=DOM.getParent(n,".mce_emotions_icon");if(p){var html=p.innerText;"IMG"===n.nodeName&&(n.setAttribute("src",ed.documentBaseURI.toRelative(n.src)),html=p.innerHTML),ed.execCommand("mceInsertContent",!1,html)}}var self=this,ed=this.editor;if("emotions"!==n)return null;var ctrl=cm.createSplitButton("emotions",{title:"emotions.desc",onselect:function(elm){insertEmoticon(elm)}});return ctrl.onRenderMenu.add(function(c,m){var item=m.add({onclick:function(e){e.preventDefault(),item.setSelected(!1),insertEmoticon(e.target),m.hideMenu()},html:'<div id="'+ed.id+'_emotions_panel" class="mceEmoticonsMenu"></div>'});m.onShowMenu.add(function(){DOM.setHTML(ed.id+"_emotions_panel",self.content)})}),ed.onRemove.add(function(){ctrl.destroy()}),ctrl}}),tinymce.PluginManager.add("emotions",tinymce.plugins.EmotionsPlugin)}();