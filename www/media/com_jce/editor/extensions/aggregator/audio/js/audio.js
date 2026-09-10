/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
WFAggregator.add("audio", {
    params: {},
    props: {
        autoplay: 0,
        loop: 0,
        controls: 1,
        muted: 0,
        preload: "auto"
    },
    setup: function() {
        var x = 0;
        $.each(this.params, function(k, v) {
            "attributes" == k ? $.each(v, function(key, value) {
                var $repeatable = $(".media_option.audio .uk-repeatable"), $repeatable = (0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
                $repeatable = $(".media_option.audio .uk-repeatable")), $repeatable.eq(x).find("input, select"));
                $repeatable.eq(0).val(key), $repeatable.eq(1).val(value), x++;
            }) : $("#audio_" + k).val(v).filter(":checkbox, :radio").prop("checked", !!v);
        });
    },
    getTitle: function() {
        return this.title || this.name;
    },
    getType: function() {
        return "audio";
    },
    isSupported: function(v) {
        return !!v && (v = v.split("?")[0], !!/\.(mp3|oga|webm|wav|m4a|aiff)$/.test(v)) && "audio";
    },
    getValues: function(data) {
        var self = this, sources = [], agent = ($("input[id], select[id]", ".media_option.audio").each(function() {
            var key = $(this).attr("id"), val = $(this).val();
            return !key || ("checkbox" === this.type && (val = this.checked ? 1 : 0), 
            "preload" == key && (val = val || "auto"), key in self.props && val == self.props[key]) || void (data[key] = val);
        }), $('input[name="audio_source[]"]').each(function() {
            var val = $(this).val();
            val !== data.src && sources.push(val);
        }), sources.length && (data.audio_source = sources), delete data.width, 
        delete data.height, navigator.userAgent.match(/(Opera|Chrome|Safari|Gecko)/));
        return agent && (data.classes += " mce-object-agent-" + agent[0].toLowerCase()), 
        $(".uk-repeatable", "#audio_attributes").each(function() {
            var elements = $("input, select", this), key = $(elements).eq(0).val(), elements = $(elements).eq(1).val();
            key && (data["audio_" + key] = elements);
        }), data;
    },
    setValues: function(data) {
        var x = 0, self = this;
        return $.each(data, function(key, val) {
            if (-1 === key.indexOf("audio_")) return !0;
            if ((key = key.substr(key.indexOf("_") + 1)) in self.props) return !0;
            "" != val && 1 != val || (val = key);
            var $repeatable = $(".uk-repeatable", "#audio_attributes"), $repeatable = (0 < x && ($repeatable.eq(0).clone(!0).appendTo($repeatable.parent()), 
            $repeatable = $(".uk-repeatable", "#audio_attributes")), $repeatable.eq(x).find("input, select"));
            $repeatable.eq(0).val(key), $repeatable.eq(1).val(val), x++;
        }), data;
    },
    getAttributes: function(src) {
        return {
            width: "",
            height: ""
        };
    }
});