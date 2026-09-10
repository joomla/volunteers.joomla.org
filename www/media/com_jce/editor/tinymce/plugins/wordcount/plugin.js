/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, Delay = tinymce.util.Delay;
    tinymce.PluginManager.add("wordcount", function(ed, url) {
        var countre = ed.getParam("wordcount_countregex", /[\w\u2019\x27\-\u00C0-\u1FFF]+/g), cleanre = ed.getParam("wordcount_cleanregex", /[0-9.(),;:!?%#$?\x27\x22_+=\\\/\-]*/g), update_rate = ed.getParam("wordcount_update_rate", 200), target_id = ed.id + "_word_count", count = (ed.onWordCount = new tinymce.util.Dispatcher(this), 
        0);
        function processText(tx) {
            var tc = 0;
            return tc = tx && (tx = (tx = (tx = (tx = (tx = tx.replace(/\.\.\./g, " ")).replace(/<.[^<>]*?>/g, " ").replace(/&nbsp;|&#160;/gi, " ")).replace(/(\w+)(&#?[a-z0-9]+;)+(\w+)/i, "$1$3").replace(/&.+?;/g, " ")).replace(cleanre, "")).match(countre)) ? tx.length : tc;
        }
        var limit = parseInt(ed.getParam("wordcount_limit", 0), 10), showAlert = ed.getParam("wordcount_alert", 0);
        function updateLabel(value) {
            DOM.removeClass(target_id, "mceWordCountLimit"), value < 0 && DOM.addClass(target_id, "mceWordCountLimit"), 
            limit ? DOM.setAttrib(target_id, "title", ed.getLang("wordcount.remain", "Words Remaining:")) : DOM.setAttrib(target_id, "title", ed.getLang("wordcount.words", "Words:")), 
            DOM.setHTML(target_id, value.toString());
        }
        function countChars() {
            ed.destroyed || (count = processText(ed.getContent({
                format: "raw",
                no_events: !0
            })), (count = limit ? limit - count : count) < 0 && showAlert && ed.windowManager.alert(ed.getLang("wordcount.alert", "You have reached the word limit set for this content.")), 
            updateLabel(count), ed.onWordCount.dispatch(ed, count));
        }
        ed.onPostRender.add(function(ed, cm) {
            var statusbar;
            target_id = ed.getParam("wordcount_target_id", target_id), DOM.get(target_id) || (statusbar = DOM.select("div.mceStatusbar", ed.getContainer())).length && (ed = ed.getLang("wordcount.selection", "Words Selected:"), 
            DOM.add(statusbar[0], "div", {
                class: "mceWordCount"
            }, '<span title="' + ed + '" id="' + target_id + '" class="mceText">0</span>'));
        });
        var countAll = Delay.debounce(function() {
            countChars();
        }, update_rate), update_rate = Delay.debounce(function() {
            var sel, rng;
            ed.destroyed || (rng = ed.selection.getRng(), sel = ed.selection.getSel(), 
            rng.collapsed ? updateLabel(count) : (rng = processText(rng.text || (sel.toString ? sel.toString() : "")), 
            DOM.removeClass(target_id, "mceWordCountLimit"), DOM.setAttrib(target_id, "title", ed.getLang("wordcount.selection", "Words Selected:")), 
            DOM.setHTML(target_id, rng.toString())));
        }, update_rate);
        ed.onPreInit.add(function() {
            ed.on("keyup setcontent undo redo", countAll);
        }), ed.onSelectionChange.add(update_rate), ed.onInit.add(countAll);
    });
}();