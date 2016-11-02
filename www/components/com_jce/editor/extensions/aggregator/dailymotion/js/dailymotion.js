/* JCE Editor - 2.5.31 | 25 October 2016 | http://www.joomlacontenteditor.net | Copyright (C) 2006 - 2016 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
WFAggregator.add('dailymotion',{params:{width:480,height:270,autoPlay:false},props:{autoPlay:0,start:0},setup:function(){$('#dailymotion_autoPlay').toggle(this.params.autoPlay);},getTitle:function(){return this.title||this.name;},getType:function(){return'iframe';},isSupported:function(v){if(typeof v=='object'){v=v.src||v.data||'';}
if(/dai\.?ly(motion)?(\.com)?/.test(v)){return'dailymotion';}
return false;},getValues:function(src){var self=this,data={},args={},type=this.getType(),id='';if(src.indexOf('=')!==-1){$.extend(args,$.String.query(src));}
$('input, select','#dailymotion_options').each(function(){var k=$(this).attr('id'),v=$(this).val();k=k.substr(k.indexOf('_')+1);if($(this).is(':checkbox')){v=$(this).is(':checked')?1:0;}
if(self.props[k]===v||v===''){return;}
args[k]=v;});var m=src.match(/dai\.?ly(motion\.com)?\/(swf|video)?\/?([a-z0-9]+)_?/);if(m){id=m[4];}
src='//www.dailymotion.com/embed/video/'+id;var query=$.param(args);if(query){src=src+(/\?/.test(src)?'&':'?')+query;}
data.src=src;$.extend(data,{frameborder:0,allowfullscreen:"allowfullscreen"});return data;},setValues:function(data){var self=this,src=data.src||data.data||'',id='';if(!src){return data;}
var query=$.String.query(src);$.extend(data,query);src=src.replace(/&amp;/g,'&');var m=src.match(/dai\.?ly(motion\.com)?\/(swf|video)?\/?([a-z0-9]+)_?/);if(m){id=m[4];}
src='//dai.ly/'+id;data.src=src;return data;},getAttributes:function(src){var args={},data=this.setValues({src:src})||{};$.each(data,function(k,v){if(k=='src'){return;}
args['dailymotion_'+k]=v;});$.extend(args,{'src':data.src||src,'width':this.params.width,'height':this.params.height});return args;},setAttributes:function(){},onSelectFile:function(){},onInsert:function(){}});