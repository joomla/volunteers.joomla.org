/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
tinymce.PluginManager.add("textcase", function(ed, url) {
    ed.addCommand("mceUpperCase", function() {
        var s, text;
        s = ed.selection, text = s.getContent(), s.setContent(text.toUpperCase());
    }), ed.addCommand("mceLowerCase", function() {
        var s, text;
        s = ed.selection, text = s.getContent(), s.setContent(text.toLowerCase());
    }), ed.addCommand("mceTitleCase", function() {
        var s, text;
        s = ed.selection, text = (text = (text = s.getContent()).toLowerCase()).replace(/(?:^|\s)[\u0000-\u1FFF]/g, function(match) {
            return match.toUpperCase();
        }), s.setContent(text);
    }), ed.addCommand("mceSentenceCase", function() {
        var s, text;
        s = ed.selection, text = (text = s.getContent()).toLowerCase().replace(/([\u0000-\u1FFF])/, function(a, b) {
            return b.toUpperCase();
        }).replace(/(\.\s?)([\u0000-\u1FFF])/gi, function(a, b, c) {
            return b + c.toUpperCase();
        }), s.setContent(text);
    }), ed.onNodeChange.add(function(ed, cm, n, co) {
        cm.setDisabled("textcase", co);
    }), this.createControl = function(n, cm) {
        return "textcase" !== n ? null : ((n = cm.createSplitButton("textcase", {
            title: "textcase.uppercase",
            icon: "uppercase",
            onclick: function() {
                ed.execCommand("mceUpperCase");
            }
        })).onRenderMenu.add(function(c, m) {
            m.add({
                title: "textcase.uppercase",
                icon: "uppercase",
                onclick: function() {
                    ed.execCommand("mceUpperCase");
                }
            }), m.add({
                title: "textcase.lowercase",
                icon: "lowercase",
                onclick: function() {
                    ed.execCommand("mceLowerCase");
                }
            }), m.add({
                title: "textcase.sentencecase",
                icon: "sentencecase",
                onclick: function() {
                    ed.execCommand("mceSentenceCase");
                }
            }), m.add({
                title: "textcase.titlecase",
                icon: "titlecase",
                onclick: function() {
                    ed.execCommand("mceTitleCase");
                }
            });
        }), n);
    };
});