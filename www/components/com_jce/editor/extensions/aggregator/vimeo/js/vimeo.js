/* jce - 2.9.20 | 2022-02-10 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2022 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
WFAggregator.add("vimeo",{params:{width:480,height:480,embed:!0},props:{color:"",autoplay:0,loop:0,fullscreen:1,dnt:0},setup:function(){$("#vimeo_embed").toggle(this.params.embed),$.each(this.params,function(k,v){$("#vimeo_"+k).val(v).filter(":checkbox, :radio").prop("checked",!!v)})},getTitle:function(){return this.title||this.name},getType:function(){return"iframe"},isSupported:function(v){return"object"==typeof v&&(v=v.src||v.data||""),!!/vimeo(.+)?\/(.+)/.test(v)&&(!/\/external\//.test(v)&&"vimeo")},getValues:function(src){var self=this,data={},args={},id="";if(src.indexOf("=")!==-1&&$.extend(args,Wf.String.query(src)),$("input, select","#vimeo_options").not("#vimeo_embed").each(function(){var k=$(this).attr("id"),v=$(this).val();k=k.substr(k.indexOf("_")+1),$(this).is(":checkbox")&&(v=$(this).is(":checked")?1:0),self.props[k]!=v&&""!==v&&("color"===k&&"#"===v.charAt(0)&&(v=v.substr(1)),args[k]=v)}),args.clip_id)id=args.clip_id;else{var id="",hash="",matches=/vimeo\.com\/([0-9]+)\/?([a-z0-9]+)?/.exec(src);if(matches&&tinymce.is(matches,"array")){var id=matches[1];matches.length>2&&(hash=matches[2])}id+=hash?"?h="+hash:""}src="https://player.vimeo.com/video/"+id;var query=$.param(args);return query&&(src=src+(/\?/.test(src)?"&":"?")+query),data.src=src,$.extend(data,{frameborder:0}),0!==args.fullscreen&&$.extend(data,{allowfullscreen:!0}),data},setValues:function(data){var self=this,src=data.src||data.data||"",id="";if(!src)return data;var query=Wf.String.query(src);if(src=src.replace(/&amp;/g,"&"),/moogaloop.swf/.test(src))data.vimeo_embed=!0,id=query.clip_id,delete query.clip_id,delete data.clip_id,$.each(["portrait","title","byline"],function(i,s){delete data["show_"+s]});else{var id="",hash="",matches=/vimeo\.com\/(?:\w+\/){0,3}((?:[0-9]+\b)(?:\/[a-z0-9]+)?)/.exec(src);if(matches&&"array"==$.type(matches)){var params=matches[1].split("/"),id=params[0];2==params.length&&(hash=params[1]),id+=hash?"/"+hash:""}}return $.each(query,function(key,val){return self.props[key]===val||("color"==key&&"#"!==val.charAt(0)&&(val="#"+val),"autoplay"==key&&(val=parseInt(val,10)),void(data["vimeo_"+key]=val))}),src="https://vimeo.com/"+id,$.each(data,function(key,val){/^iframe_(allow|frameborder|allowfullscreen)/.test(key)&&delete data[key]}),data.src=src,data},getAttributes:function(src){var args={},data=this.setValues({src:src})||{};return $.each(data,function(k,v){"src"!=k&&(args["vimeo_"+k]=v)}),$.extend(args,{src:data.src||src,width:this.params.width,height:this.params.height}),args},setAttributes:function(){},onSelectFile:function(){},onInsert:function(){}});