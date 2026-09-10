/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
var JCEMediaBox = {
    Popup: {
        addons: {},
        setAddons: function(n, o) {
            void 0 === this.addons[n] && (this.addons[n] = {}), $.extend(this.addons[n], o);
        },
        getAddons: function(n) {
            return n ? this.addons[n] : this.addons;
        },
        getAddon: function(v, n) {
            var r, cp = !1, n = this.getAddons(n);
            return $.each(n, function(addon, o) {
                o = o[addon] || function() {};
                void 0 !== (r = o.call(this, v)) && (cp = r);
            }), cp;
        }
    },
    trim: function(s) {
        return $.trim(s);
    }
};

WFPopups.addPopup("jcemediabox", {
    params: {
        popup_group: "",
        popup_icon: 1,
        popup_icon_position: "",
        popup_autopopup: "",
        popup_hide: 0,
        popup_mediatype: ""
    },
    setup: function() {
        var self = this;
        $("#jcemediabox_popup_icon").on("change", function() {
            self.setIcon();
        }), $.each(this.params, function(k, v) {
            "popup_icon_position" === k && (v = v.replace("icon-", "zoom-")), $("#jcemediabox_" + k).val(v);
        });
    },
    check: function(n) {
        return /(jce(popup|_popup|lightbox)|wfpopup)/.test(n.className) || n.getAttribute("data-mediabox");
    },
    getMediaType: function(n) {
        var mt, o;
        switch (n.type) {
          case "image/gif":
          case "image/jpeg":
          case "image/png":
          case "image/*":
          case "image":
            mt = "image";
            break;

          case "iframe":
            mt = "iframe";
            break;

          case "director":
          case "application/x-director":
            mt = "application/x-director";
            break;

          case "windowsmedia":
          case "mplayer":
          case "application/x-mplayer2":
            mt = "application/x-mplayer2";
            break;

          case "quicktime":
          case "video/quicktime":
            mt = "video/quicktime";
            break;

          case "real":
          case "realaudio":
          case "audio/x-pn-realaudio-plugin":
            mt = "audio/x-pn-realaudio-plugin";
            break;

          case "divx":
          case "video/divx":
            mt = "video/divx";
            break;

          case "flash":
          case "application/x-shockwave-flash":
            mt = "application/x-shockwave-flash";
            break;

          case "ajax":
          case "text/xml":
          case "text/html":
            mt = "text/html";
        }
        return (mt = !mt && n.href && (JCEMediaBox.options = {
            popup: {
                google_viewer: 0,
                pdfjs: 0
            }
        }, o = JCEMediaBox.Popup.getAddon(n.href)) && o.type ? o.type : mt) || n.type || "";
    },
    getImageType: function(s) {
        s = /\.(jp(eg|g)|png|bmp|gif|tiff)$/.exec(s);
        return s ? ("jpg" === s[1] && (s[1] = "jpeg"), "image/" + s[1]) : "image/jpeg";
    },
    remove: function(n) {
        var ed = tinyMCEPopup.editor;
        $.each([ "jcepopup", "jcelightbox", "jcebox", "icon-left", "icon-right", "icon-top-left", "icon-top-right", "icon-bottom-left", "icon-bottom-right", "zoom-left", "zoom-right", "zoom-top-left", "zoom-top-right", "zoom-bottom-left", "zoom-bottom-right", "noicon", "noshow", "autopopup-single", "autopopup-multiple" ], function(i, v) {
            ed.dom.removeClass(n, v);
        }), ed.dom.setAttrib(n, "data-mediabox", null), ed.dom.setAttrib(n, "data-mediabox-title", null), 
        ed.dom.setAttrib(n, "data-mediabox-caption", null), ed.dom.setAttrib(n, "data-mediabox-group", null);
    },
    convertData: function(s) {
        var data;
        return /^{[\w\W]+}$/.test(s) ? $.parseJSON(function(s) {
            return s.replace(/:"([^"]+)"/, function(a, b) {
                return ':"' + b.replace(/^\s+|\s+$/, "").replace(/\s*::\s*/, "::") + '"';
            });
        }(s)) : /\w+\[[^\]]+\]/.test(s) ? (data = {}, tinymce.each(tinymce.explode(s, ";"), function(p) {
            p = p.match(/([\w-]+)\[(.*)\]$/);
            p && 3 === p.length && (data[p[1]] = p[2]);
        }), data) : {};
    },
    getAttributes: function(n, index, callback) {
        var v, ed = tinyMCEPopup.editor, data = {}, rel = ed.dom.getAttrib(n, "rel"), icon = /noicon/g.test(n.className), hide = /noshow/g.test(n.className), hide = (/(autopopup(.?|-single|-multiple))/.test(n.className) && (v = /autopopup-multiple/.test(n.className) ? "autopopup-multiple" : "autopopup-single", 
        $("#jcemediabox_popup_autopopup").val(v)), $("#jcemediabox_popup_icon").val(icon ? 0 : 1), 
        $("#jcemediabox_popup_icon_position").prop("disabled", icon), $("#jcemediabox_popup_hide").val(hide ? 1 : 0), 
        (icon = /(zoom|icon)-(top-right|top-left|bottom-right|bottom-left|left|right)/.exec(n.className)) && (v = icon[0]) && (v = v.replace("icon-", "zoom-"), 
        $("#jcemediabox_popup_icon_position").val(v)), /(^|\\s+)alternate|stylesheet|start|next|prev|contents|index|glossary|copyright|chapter|section|subsection|appendix|help|bookmark|nofollow|noopener|noreferrer|licence|tag|friend(\\s+|$)/gi), icon = ed.dom.getAttrib(n, "data-json") || ed.dom.getAttrib(n, "data-mediabox"), x = (icon && (data = this.convertData(icon)), 
        rel && /\w+\[.*\]/.test(rel) ? (v = "", (icon = hide.exec(rel)) && (v = icon[1], 
        rel = rel.replace(hide, "")), /^\w+\[/.test(rel) && ((data = this.convertData($.trim(rel)) || {}).rel = v)) : (icon = $.trim(rel.replace(hide, "")), 
        $("#jcemediabox_popup_group").val(icon)), $.isEmptyObject(data) && $.each(ed.dom.getAttribs(n), function(i, at) {
            var k, at = at.name || at.nodeName;
            at && -1 !== at.indexOf("data-mediabox-") && (k = at.substr(14), data[k] = ed.dom.getAttrib(n, at));
        }), data.title && /::/.test(data.title) && (1 < (v = data.title.split("::")).length && (data.caption = v[1]), 
        data.title = v[0]), $.each(data, function(k, v) {
            if ($("#jcemediabox_popup_" + k).get(0) && "" !== v) {
                if ("title" == k || "caption" == k || "group" == k) try {
                    v = decodeURIComponent(v);
                } catch (e) {}
                v = tinymce.DOM.decode(v), $("#jcemediabox_popup_" + k).val(v).trigger("change"), 
                "title" != k && "caption" != k || $('input[name^="jcemediabox_popup_' + k + '"]').eq(index).val(v), 
                delete data[k];
            }
        }), $.each([ "href", "type", "data-mediabox-width", "data-mediabox-height" ], function(i, name) {
            var val = ed.dom.getAttrib(n, name);
            val && (0 === (name = "href" === name ? "src" : name).indexOf("data-mediabox-") && (name = name.substr(14)), 
            data[name] = val);
        }), data = callback(data), 0);
        return $.each(data, function(k, v) {
            if ("src" == k || "width" == k || "height" == k) return !0;
            if ("" !== v) {
                try {
                    v = decodeURIComponent(v);
                } catch (e) {}
                var n = $(".uk-repeatable", "#jcemediabox_popup_params").eq(0), n = (0 < x && $(n).clone(!0).appendTo($(n).parent()), 
                $(".uk-repeatable", "#jcemediabox_popup_params").eq(x).find("input, select"));
                $(n).eq(0).val(k), $(n).eq(1).val(v);
            }
            x++;
        }), $("#jcemediabox_popup_mediatype").val(this.getMediaType(n)), data;
    },
    setAttributes: function(n, args, index) {
        var ed = tinyMCEPopup.editor, auto = (index = index || 0, this.remove(n), 
        index = index || 0, ed.dom.addClass(n, "jcepopup"), ed.dom.setAttrib(n, "data-mediabox", 1), 
        $("#jcemediabox_popup_autopopup").val()), data = (auto && ed.dom.addClass(n, auto), 
        args.data || {}), auto = (args.title && (ed.dom.setAttrib(n, "title", args.title), 
        delete args.title), $.each([ "group", "width", "height", "title", "caption" ], function(i, k) {
            var mv, v = $("#jcemediabox_popup_" + k).val() || args[k] || "";
            "title" != k && "caption" != k || void 0 !== (mv = $('input[name^="jcemediabox_popup_' + k + '"]').eq(index).val()) && (v = mv), 
            data[k] = v;
        }), $(".uk-repeatable", "#jcemediabox_popup_params").each(function() {
            var k = $('input[name^="jcemediabox_popup_params_name"]', this).val(), v = $('input[name^="jcemediabox_popup_params_value"]', this).val();
            "" !== k && "" !== v && (data[k] = v);
        }), $("#jcemediabox_popup_mediatype").val() || n.type || args.type || "");
        "image" == auto && (auto = this.getImageType(n.href)), ed.dom.setAttrib(n, "type", auto), 
        data.type && delete data.type;
        auto = (auto = ed.dom.getAttrib(n, "rel", "")) && auto.replace(/([a-z0-9]+)(\[([^\]]+)\]);?/gi, "");
        $(".uk-repeatable", "#jcemediabox_popup_params").each(function() {
            var elements = $("input, select", this), key = $(elements).eq(0).val(), elements = $(elements).eq(1).val();
            data[key] = elements;
        });
        for (var attrs = n.attributes, i = attrs.length - 1; 0 <= i; i--) {
            var attrName = attrs[i].name;
            attrName && -1 !== attrName.indexOf("data-mediabox-") && n.removeAttribute(attrName);
        }
        $.each(data, function(k, v) {
            if ("src" == k) return !0;
            ed.dom.setAttrib(n, "data-mediabox-" + k, v);
        }), ed.dom.setAttrib(n, "rel", $.trim(auto)), 0 == $("#jcemediabox_popup_icon").val() ? ed.dom.addClass(n, "noicon") : ed.dom.addClass(n, $("#jcemediabox_popup_icon_position").val()), 
        1 == $("#jcemediabox_popup_hide").val() && ed.dom.addClass(n, "noshow");
    },
    setIcon: function() {
        var v = $("#jcemediabox_popup_icon").val();
        parseInt(v, 10) ? $("#jcemediabox_popup_icon_position").prop("disabled", !1).removeAttr("disabled") : $("#jcemediabox_popup_icon_position").attr("disabled", "disabled");
    },
    onSelect: function() {},
    onSelectFile: function(args) {
        $.each(args, function(k, v) {
            $("#jcemediabox_popup_" + k).val(v);
        });
    }
});