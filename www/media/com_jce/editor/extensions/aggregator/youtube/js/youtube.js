/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
WFAggregator.add("youtube", {
    params: {
        width: 560,
        height: 315,
        embed: !0
    },
    props: {
        rel: 1,
        autoplay: 0,
        mute: 0,
        controls: 1,
        modestbranding: 0,
        enablejsapi: 0,
        loop: 0,
        playlist: "",
        start: "",
        end: "",
        privacy: 0
    },
    setup: function() {
        $.each(this.params, function(k, v) {
            var x;
            if ("attributes" == k) return x = 0, $.each(v, function(key, value) {
                var $repeatable = $(".uk-repeatable", "#youtube_attributes"), $repeatable = (0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
                $repeatable = $(".uk-repeatable", "#youtube_attributes")), $repeatable.eq(x).find("input, select"));
                $repeatable.eq(0).val(key), $repeatable.eq(1).val(value), x++;
            }), !0;
            $("#youtube_" + k).val(v).filter(":checkbox, :radio").prop("checked", !!v);
        }), $("#youtube_autoplay").on("change", function() {
            this.checked && $("#youtube_mute").prop("checked", !0);
        }).trigger("change");
    },
    getTitle: function() {
        return this.title || this.name;
    },
    getType: function() {
        return $("#youtube_embed:visible").is(":checked") ? "flash" : "iframe";
    },
    isSupported: function(v) {
        return !!v && !!/youtu(\.)?be(.+)?\/(.+)/.test(v) && "youtube";
    },
    getValues: function(data) {
        var id, self = this, args = {}, type = this.getType(), src = data.src, u = this.parseURL(src), u = ($.extend(args, u.query), 
        src = src.replace(/^(http:)?\/\//, "https://"), $(":input", "#youtube_options").not("#youtube_embed, #youtube_https, #youtube_privacy").each(function() {
            var k = $(this).attr("id"), v = $(this).val();
            if (!k) return !0;
            k = k.substr(k.indexOf("_") + 1), $(this).is(":checkbox") && (v = $(this).is(":checked") ? 1 : 0), 
            self.props[k] != v && "" !== v && (args[k] = v);
        }), 1 == args.autoplay && (args.mute = 1), src = src.replace(/youtu(\.)?be([^\/]+)?\/(.+)/, function(a, b, c, d) {
            return d = d.replace(/(watch\?v=|v\/|embed\/|live\/)/, ""), b && !c && (c = ".com"), 
            id = d.replace(/([^\?&#]+)/, function($0, $1) {
                return $1;
            }), "youtube" + c + "/" + ("iframe" == type ? "embed" : "v") + "/" + d;
        }), id && args.loop && !args.playlist && (args.playlist = id), src = $("#youtube_privacy").is(":checked") ? src.replace(/youtube\./, "youtube-nocookie.") : src.replace(/youtube-nocookie\./, "youtube."), 
        "iframe" == type ? $.extend(data, {
            allowfullscreen: "allowfullscreen",
            frameborder: 0
        }) : $.extend(!0, data, {
            param: {
                allowfullscreen: "allowfullscreen",
                wmode: "opaque"
            }
        }), $(".uk-repeatable", "#youtube_params").each(function() {
            var key = $('input[name^="youtube_params_name"]', this).val(), value = $('input[name^="youtube_params_value"]', this).val();
            key && (args[key] = value);
        }), $.param(args));
        return u && (src = src + (/\?/.test(src) ? "&" : "?") + u), data.src = src, 
        $(".uk-repeatable", "#youtube_attributes").each(function() {
            var elements = $("input, select", this), key = $(elements).eq(0).val(), elements = $(elements).eq(1).val();
            key && (data["youtube_" + key] = elements);
        }), data;
    },
    parseURL: function(url) {
        var o = {};
        try {
            var urlObj = new URL(url);
            o.host = urlObj.hostname, o.path = urlObj.pathname, o.query = {}, urlObj.searchParams.forEach(function(value, key) {
                o.query[key] = value;
            }), o.anchor = urlObj.hash ? urlObj.hash.substring(1) : null;
        } catch (e) {
            console.error("Invalid URL:", url);
        }
        return o;
    },
    setValues: function(data) {
        var attribs, params, u, s, x, self = this, id = "", src = data.src || data.data || "";
        return src && (attribs = {}, params = {}, src = "https://" + (u = this.parseURL(src)).host + u.path, 
        $.each(data, function(key, val) {
            0 === key.indexOf("youtube_") && (key = key.substr(key.indexOf("_") + 1), 
            "" !== val && !0 !== val || (val = key), attribs[key] = val, delete data[key]);
        }), -1 !== src.indexOf("youtube-nocookie") ? data.youtube_privacy = 1 : data.youtube_privacy = 0, 
        u.query.v ? id = u.query.v : (s = /\/?(embed|live|shorts|v)?\/([\w-]+)\b/.exec(u.path)) && "array" === $.type(s) && (id = s.pop()), 
        $.each(u.query, function(key, val) {
            if ("v" == key) return !0;
            "t" == key && (key = "start", val = val.replace(/(\D)/g, ""));
            try {
                val = decodeURIComponent(val);
            } catch (e) {}
            return "autoplay" == key && (val = parseInt(val, 10)), "mute" == key && (val = parseInt(val, 10)), 
            "playlist" == key && val == id || "wmode" == key || self.props[key] === val || (void 0 !== self.props[key] ? (data["youtube_" + key] = val, 
            !0) : (params[key] = val, void delete data[key]));
        }), src = src.replace(/youtu(\.)?be([^\/]+)?\/(.+)/, function(a, b, c, d) {
            var args = "youtube";
            return b && (args += ".com"), c && (args += c), args += "/embed/" + id, 
            u.anchor && (args += "#" + u.anchor.replace(/(\?|&)(.+)/, "")), args;
        }).replace(/\/\/youtube/i, "//www.youtube"), $.each(data, function(key, val) {
            /^iframe_(allow|frameborder|allowfullscreen)/.test(key) && delete data[key];
        }), x = 0, $.each(params, function(key, val) {
            var $repeatable = $(".uk-repeatable", "#youtube_params");
            if ("feature" == key && "oembed" == val) return !0;
            0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
            $repeatable = $(".uk-repeatable", "#youtube_params"));
            $repeatable = $repeatable.eq(x).find("input, select");
            $repeatable.eq(0).val(key), $repeatable.eq(1).val(val), x++;
        }), x = 0, $.each(attribs, function(key, val) {
            var $repeatable = $(".uk-repeatable", "#youtube_attributes"), $repeatable = (0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
            $repeatable = $(".uk-repeatable", "#youtube_attributes")), $repeatable.eq(x).find("input, select"));
            $repeatable.eq(0).val(key), $repeatable.eq(1).val(val), x++;
        }), data.src = src), data;
    },
    getAttributes: function(src) {
        var args = {}, data = this.setValues({
            src: src
        }) || {}, width = ($.each(data, function(k, v) {
            "src" !== k && (args[k] = v);
        }), this.params.width), height = this.params.height;
        return -1 !== src.indexOf("/shorts/") && (width = this.params.height, height = this.params.width), 
        args = $.extend(args, {
            src: data.src || src,
            width: width,
            height: height
        });
    },
    setAttributes: function() {},
    onSelectFile: function() {},
    onInsert: function() {}
});