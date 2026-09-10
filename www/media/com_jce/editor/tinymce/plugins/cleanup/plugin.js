/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
import split from "./src/utils/split";

import {
    TAGS,
    PADDED_RX
} from "./src/constants";

import createPadding from "./src/filters/padding";

import {
    processAttributes
} from "./src/filters/processAttributes";

import {
    convertFromGeshi,
    convertToGeshi
} from "./src/geshi";

var each = tinymce.each, Node = tinymce.html.Node;

tinymce.PluginManager.add("cleanup", function(ed, url) {
    !1 === ed.settings.verify_html && (ed.settings.validate = !1);
    var padding = createPadding(Node);
    ed.onPreInit.add(function() {
        var elements;
        ed.serializer.addAttributeFilter("data-mce-caret", function(nodes) {
            for (var i = nodes.length; i--; ) nodes[i].remove();
        }), !1 === ed.settings.remove_trailing_brs && ed.serializer.addAttributeFilter("data-mce-bogus", function(nodes) {
            for (var node, textNode, i = nodes.length; i--; ) "br" === (node = nodes[i]).name && (node.prev || node.next ? node.remove() : ((textNode = new Node("#text", 3)).value = "\xa0", 
            node.replace(textNode)));
        }), ed.serializer.addAttributeFilter("data-mce-tmp", function(nodes, name) {
            for (var i = nodes.length; i--; ) nodes[i].attr("data-mce-tmp", null);
        }), ed.parser.addAttributeFilter("data-mce-tmp", function(nodes, name) {
            for (var i = nodes.length; i--; ) nodes[i].attr("data-mce-tmp", null);
        }), !1 !== ed.settings.verify_html && (ed.settings.allow_event_attributes || each(ed.schema.elements, function(elm) {
            if (!elm.attributesOrder || 0 === elm.attributesOrder.length) return !0;
            each(elm.attributes, function(obj, name) {
                0 === name.indexOf("on") && (delete elm.attributes[name], elm.attributesOrder.splice(tinymce.inArray(elm, elm.attributesOrder, name), 1));
            });
        }), elements = ed.schema.elements, each(split("ol ul sub sup blockquote font table tbody tr strong b"), function(name) {
            elements[name] && (elements[name].removeEmpty = !1);
        }), ed.getParam("pad_empty_tags", !0) || each(elements, function(v, k) {
            v.paddEmpty && (v.paddEmpty = !1);
        }), each(elements, function(v, k) {
            if (0 === k.indexOf("mce:")) return !0;
            -1 === tinymce.inArray(TAGS, k) && ed.schema.addCustomElements(k);
        })), ed.parser.addNodeFilter("a,i,span,li", function(nodes, name) {
            padding.ensureEmptyInlineNodes(nodes, name);
        }), ed.serializer.addAttributeFilter("data-mce-empty", function(nodes) {
            padding.cleanupEmptyInlineNodes(nodes);
        });
    }), !1 === ed.settings.verify_html && ed.addCommand("mceCleanup", function() {
        var s = ed.settings, se = ed.selection, bm = se.getBookmark(), content = ed.getContent({
            cleanup: !0
        }), s = (s.verify_html = !0, new tinymce.html.Schema(s)), content = new tinymce.html.Serializer({
            validate: !0
        }, s).serialize(new tinymce.html.DomParser({
            validate: !0,
            allow_event_attributes: !!ed.settings.allow_event_attributes
        }, s).parse(content));
        ed.setContent(content, {
            cleanup: !0
        }), se.moveToBookmark(bm);
    }), ed.onBeforeSetContent.add(function(ed, o) {
        o.content = o.content.replace(/^<br>/, ""), o.content = convertFromGeshi(o.content), 
        o.content = padding.paddEmptyTags(o.content), o.content = processAttributes(ed, o.content);
    }), ed.onPostProcess.add(function(ed, o) {
        o.set && (o.content = convertFromGeshi(o.content)), o.get && (o.content = convertToGeshi(o.content), 
        o.content = o.content.replace(/<a([^>]*)class="jce(box|popup|lightbox|tooltip|_tooltip)"([^>]*)><\/a>/gi, ""), 
        o.content = o.content.replace(/<span class="jce(box|popup|lightbox|tooltip|_tooltip)">(.*?)<\/span>/gi, "$2"), 
        o.content = o.content.replace(/_mce_(src|href|style|coords|shape)="([^"]+)"\s*?/gi, ""), 
        !1 === ed.settings.validate && (o.content = o.content.replace(/<body([^>]*)>([\s\S]*)<\/body>/, "$2"), 
        ed.getParam("remove_tag_padding") || (o.content = o.content.replace(/<(p|h1|h2|h3|h4|h5|h6|th|td|pre|div|address|caption)\b([^>]*)><\/\1>/gi, "<$1$2>&nbsp;</$1>"))), 
        o.content = o.content.replace(/<(a|i|span)([^>]+)>(&nbsp;|\u00a0)<\/\1>/gi, function(match, tag, attribs) {
            return attribs = attribs.replace('data-mce-empty="1"', ""), "<" + tag + " " + tinymce.trim(attribs) + "></" + tag + ">";
        }), o.content = o.content.replace(/<li data-mce-empty="1">(&nbsp;|\u00a0)<\/li>/gi, "<li></li>"), 
        ed.getParam("remove_div_padding") && (o.content = o.content.replace(/<div([^>]*)>(&nbsp;|\u00a0)<\/div>/g, "<div$1></div>")), 
        !1 === ed.getParam("pad_empty_tags", !0) && (o.content = o.content.replace(PADDED_RX, "<$1$2></$1>")), 
        ed.getParam("keep_nbsp", !0) && "raw" === ed.settings.entity_encoding && (o.content = o.content.replace(/\u00a0/g, "&nbsp;")), 
        o.content = o.content.replace(/(uk|v|ng|data)-([\w-]+)=""(\s|>)/gi, "$1-$2$3"), 
        ed.settings.padd_empty_editor && (o.content = o.content.replace(/^(<div>(&nbsp;|&#160;|\s|\u00a0|)<\/div>[\r\n]*|<br(\s*\/)?>[\r\n]*)$/, "")), 
        o.content = o.content.replace(/<hr(.*)class="system-pagebreak"(.*?)\/?>/gi, '<hr$1class="system-pagebreak"$2/>'), 
        o.content = o.content.replace(/<hr id="system-readmore"(.*?)>/gi, '<hr id="system-readmore" />'));
    }), ed.onSaveContent.add(function(ed, o) {
        var entities;
        ed.getParam("cleanup_pluginmode") && (entities = {
            "&#39;": "'",
            "&amp;": "&",
            "&quot;": '"',
            "&apos;": "'"
        }, o.content = o.content.replace(/&(#39|apos|amp|quot);/gi, function(a) {
            return entities[a];
        }));
    }), ed.addButton("cleanup", {
        title: "advanced.cleanup_desc",
        cmd: "mceCleanup"
    }), this.paddEmptyTags = padding.paddEmptyTags;
});