/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM;
    tinymce.PluginManager.add("branding", function(ed, url) {
        !1 !== ed.settings.branding && (ed.onPostRender.add(function() {
            var container = ed.getContentAreaContainer();
            DOM.insertAfter(DOM.create("div", {
                class: "mceBranding"
            }, 'Powered by JCE Core. <span id="mceBrandingMessage"></span><a href="https://www.joomlacontenteditor.net/buy" target="_blank" title="Get JCE Pro">JCE Pro</a>'), container);
        }), ed.onNodeChange.add(function(ed, cm, n, co) {
            var container = ed.getContentAreaContainer(), msg = "Get more features with ";
            "IMG" === n.nodeName && (msg = "Image resizing, thumbnails and editing in "), 
            ed.dom.is(n, ".mce-item-media") && (msg = "Upload and manage audio and video with "), 
            DOM.setHTML(DOM.get("mceBrandingMessage", container), msg);
        }));
    });
}();