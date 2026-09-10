/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
WFAggregator.add("video", {
    params: {
        width: "",
        height: ""
    },
    props: {
        autoplay: 0,
        loop: 0,
        controls: 1,
        muted: 0
    },
    setup: function() {
        var x = 0;
        $.each(this.params, function(k, v) {
            "attributes" == k ? $.each(v, function(key, value) {
                var $repeatable = $(".media_option.video .uk-repeatable"), $repeatable = (0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
                $repeatable = $(".media_option.video .uk-repeatable")), $repeatable.eq(x).find("input, select"));
                $repeatable.eq(0).val(key), $repeatable.eq(1).val(value), x++;
            }) : $("#video_" + k).val(v).filter(":checkbox, :radio").prop("checked", !!v);
        });
    },
    getTitle: function() {
        return this.title || this.name;
    },
    getType: function() {
        return "video";
    },
    isSupported: function(v) {
        return !!v && (v = v.split("?")[0], !!/\.(mp4|m4v|ogv|ogg|webm)$/.test(v)) && "video";
    },
    getValues: function(data) {
        var sources = [];
        return $("input[id], select[id]", ".media_option.video").each(function() {
            var key = $(this).attr("id"), val = $(this).val();
            if (!key) return !0;
            "checkbox" === this.type && (val = !!this.checked), data[key] = val;
        }), $('input[name="video_source[]"]').each(function() {
            var val = $(this).val();
            val !== data.src && sources.push(val);
        }), sources.length && (data.video_source = sources), $(".uk-repeatable", "#video_attributes").each(function() {
            var elements = $("input, select", this), key = $(elements).eq(0).val(), elements = $(elements).eq(1).val();
            key && (data["video_" + key] = elements);
        }), data;
    },
    setValues: function(data) {
        var x = 0, self = this;
        return $.each(data, function(key, val) {
            if (-1 === key.indexOf("video_")) return !0;
            if ((key = key.substr(key.indexOf("_") + 1)) in self.props) return !0;
            "" != val && 1 != val || (val = key);
            var $repeatable = $(".uk-repeatable", "#video_attributes"), $repeatable = (0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
            $repeatable = $(".uk-repeatable", "#video_attributes")), $repeatable.eq(x).find("input, select"));
            $repeatable.eq(0).val(key), $repeatable.eq(1).val(val), x++;
        }), data;
    },
    getAttributes: function(src) {
        return {
            width: this.params.width,
            height: this.params.height
        };
    }
});