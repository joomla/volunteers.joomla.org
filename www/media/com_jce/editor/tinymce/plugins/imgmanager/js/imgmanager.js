/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($) {
    var ImageManagerDialog = {
        settings: {},
        init: function() {
            tinyMCEPopup.restoreSelection();
            var w, h, x, ed = tinyMCEPopup.editor, n = ed.selection.getNode(), self = this, params = ed.getParam("imgmanager", {}), src = ($("#insert").on("click", function(e) {
                self.insert(), e.preventDefault();
            }), decodeURIComponent(ed.dom.getAttrib(n, "src"))), src = ed.convertURL(src);
            $.each(this.settings.attributes, function(key, val) {
                val || $("#attributes-" + key).hide();
            }), $("#onmouseover, #onmouseout").addClass("uk-persistent-focus").on("click focus", function() {
                $("#onmouseover, #onmouseout").removeClass("uk-active"), $(this).addClass("uk-active");
            }), $("body").on("click.persistent-focus", function(e) {
                $(e.target).is(".uk-persistent-focus, li.file") || $(e.target).parents("li.file").length || $(".uk-persistent-focus").removeClass("uk-active");
            }), Wf.init({
                classes: params.custom_classes || []
            }), $("#alt").on("change", function() {
                "" === this.value ? $(this).removeClass("uk-edited") : $(this).addClass("uk-edited");
            }), n && "IMG" == n.nodeName ? ($(".uk-button-text", "#insert").text(tinyMCEPopup.getLang("update", "Update", !0)), 
            $("#src").val(src), $("#sample").attr({
                src: n.src
            }).attr(Wf.sizeToFit(n, {
                width: 80,
                height: 60
            })), w = Wf.getAttrib(n, "width"), h = Wf.getAttrib(n, "height"), $("#width").val(function() {
                return w ? ($(this).addClass("uk-isdirty"), w) : h ? void 0 : n.width;
            }), $("#height").val(function() {
                return h ? ($(this).addClass("uk-isdirty"), h) : w ? void 0 : n.height;
            }), $("#alt").val(function() {
                var val = ed.dom.getAttrib(n, "alt");
                if (val) return $(this).addClass("uk-edited"), val;
            }), $("#title").val(ed.dom.getAttrib(n, "title")), $.each([ "top", "right", "bottom", "left" ], function() {
                $("#margin_" + this).val(Wf.getAttrib(n, "margin-" + this));
            }), $("#border_width").val(function() {
                var v = Wf.getAttrib(n, "border-width");
                return 0 == $('option[value="' + v + '"]', this).length && $(this).append(new Option(v, v, !1, !0)), 
                v;
            }), $("#border_style").val(Wf.getAttrib(n, "border-style")), $("#border_color").val(Wf.getAttrib(n, "border-color")).trigger("change"), 
            $("#border").trigger("change"), $("#border").is(":checked") || $.each([ "border_width", "border_style", "border_color" ], function(i, k) {
                $("#" + k).val(self.settings.defaults[k]).trigger("change");
            }), $("#align").val(Wf.getAttrib(n, "align")), $("#classes").val(function() {
                var values = ed.dom.getAttrib(n, "class");
                return $.trim(values);
            }).trigger("change"), $("#style").val(ed.dom.getAttrib(n, "style")), 
            $("#id").val(ed.dom.getAttrib(n, "id")), $("#dir").val(ed.dom.getAttrib(n, "dir")), 
            $("#lang").val(ed.dom.getAttrib(n, "lang")), $("#usemap").val(ed.dom.getAttrib(n, "usemap")), 
            $("#loading").val(ed.dom.getAttrib(n, "loading")), $("#insert").button("option", "label", ed.getLang("update", "Update")), 
            $("#longdesc").val(ed.convertURL(ed.dom.getAttrib(n, "longdesc"))), 
            $.each([ "mouseover", "mouseout" ], function(i, key) {
                var val = ed.dom.getAttrib(n, "data-" + key);
                val = (val = $.trim(val)).replace(/^\s*this.src\s*=\s*\'([^\']+)\';?\s*$/, "$1").replace(/^\s*|\s*$/g, ""), 
                val = ed.convertURL(val), "mouseout" == key && (val = val || src), 
                $("#on" + key).val(val);
            }), (params = n.nextSibling) && "BR" == params.nodeName && params.style.clear && $("#clear").val(params.style.clear), 
            x = 0, params = function(ed, node) {
                for (var attrs = node.attributes, attribs = {}, i = attrs.length - 1; 0 <= i; i--) {
                    var name = attrs[i].name, value = ed.dom.getAttrib(node, name);
                    "_" !== name.charAt(0) && -1 === name.indexOf("-mce-") && (attribs[name] = value);
                }
                return attribs;
            }(ed, n), $.each(params, function(key, val) {
                if ("data-mouseover" === key || "data-mouseout" === key || 0 === key.indexOf("on")) return !0;
                if (document.getElementById(key) || "class" == key) return !0;
                try {
                    val = decodeURIComponent(val);
                } catch (e) {}
                var repeatable = $(".uk-repeatable").eq(0), repeatable = (0 < x && $(repeatable).clone(!0).appendTo($(repeatable).parent()), 
                $(".uk-repeatable").eq(x).find("input, select"));
                $(repeatable).eq(0).val(key), $(repeatable).eq(1).val(val), x++;
            })) : Wf.setDefaults(this.settings.defaults), "external" === ed.settings.filebrowser_position ? Wf.createBrowsers($("#src"), function(files) {
                files = files.shift();
                self.selectFile(files);
            }, "images") : $("#src").filebrowser().on("filebrowser:onfileclick", function(e, file, data) {
                self.selectFile(file, data);
            }).on("filebrowser:onfileinsert", function(e, file, data) {
                self.selectFile(file, data);
            }).on("filebrowser:onfileinsert", function(e, file, data) {
                self.insert();
            }), Wf.updateStyles(), $("#border").change(), $(".uk-constrain-checkbox").on("constrain:change", function(e, elms) {
                $(elms).addClass("uk-isdirty");
            }).trigger("constrain:update"), $(".uk-equalize-checkbox").trigger("equalize:update"), 
            $(".uk-form-controls select").datalist().trigger("datalist:update"), 
            $(".uk-datalist").trigger("datalist:update");
        },
        insert: function() {
            var ed = tinyMCEPopup.editor, self = this, n = ed.selection.getNode();
            if ("" === $("#src").val()) return Wf.Modal.alert(tinyMCEPopup.getLang("imgmanager_dlg.no_src", "Please enter a url for the image")), 
            !1;
            n && "IMG" === n.nodeName && "" === ed.dom.getAttrib(n, "alt") && this.insertAndClose(), 
            "" === $("#alt").val() ? Wf.Modal.confirm(tinyMCEPopup.getLang("imgmanager_dlg.missing_alt"), function(state) {
                state && self.insertAndClose();
            }, {
                width: 360,
                height: 240
            }) : this.insertAndClose();
        },
        insertAndClose: function() {
            var v, ed = tinyMCEPopup.editor, self = this, args = {}, br = "", over = (Wf.updateStyles(), 
            tinyMCEPopup.restoreSelection(), tinymce.isWebKit && ed.getWin().focus(), 
            args = {
                vspace: "",
                hspace: "",
                border: "",
                align: ""
            }, $.each([ "src", "width", "height", "alt", "title", "classes", "style", "id", "dir", "lang", "usemap", "longdesc", "loading" ], function(i, k) {
                v = $("#" + k + ":enabled").val(), "src" == k && (v = Wf.String.buildURI(v)), 
                "width" != k && "height" != k || (v = !1 !== self.settings.always_include_dimensions ? $("#" + k).val() : $("#" + k + ".uk-isdirty").val() || ""), 
                args[k = "classes" == k ? "class" : k] = v;
            }), args.onmouseover = args.onmouseout = "", $("#onmouseover").val()), out = $("#onmouseout").val();
            over || (out = ""), args = $.extend(args, {
                "data-mouseover": over ? ed.convertURL(over) : "",
                "data-mouseout": out ? ed.convertURL(out) : ""
            }), br = (over = ed.selection.getNode()).nextSibling, $(".uk-repeatable").each(function() {
                var elements = $("input, select", this), key = $(elements).eq(0).val(), elements = $(elements).eq(1).val();
                key && (args[key] = elements);
            }), over && "IMG" == over.nodeName ? (ed.dom.setAttribs(over, args), 
            br && "BR" == br.nodeName ? (!$("#clear").is(":disabled") && "" !== $("#clear").val() || ed.dom.remove(br), 
            $("#clear").is(":disabled") || "" === $("#clear").val() || ed.dom.setStyle(br, "clear", $("#clear").val())) : $("#clear").is(":disabled") || "" === $("#clear").val() || (br = ed.dom.create("br"), 
            ed.dom.setStyle(br, "clear", $("#clear").val()), ed.dom.insertAfter(br, over)), 
            ed.onUpdateMedia.dispatch(ed, {
                node: over
            })) : (ed.execCommand("mceInsertContent", !1, ed.dom.createHTML("img", $.extend({}, args, {
                id: "__mce_tmp"
            })), {
                skip_undo: 1
            }), over = ed.dom.get("__mce_tmp"), $("#clear").is(":disabled") || "" === $("#clear").val() || (br = ed.dom.create("br"), 
            ed.dom.setStyle(br, "clear", $("#clear").val()), ed.dom.insertAfter(br, over)), 
            ed.dom.setAttrib(over, "id", args.id)), ed.undoManager.add(), ed.nodeChanged(), 
            tinyMCEPopup.close();
        },
        selectFile: function(file, data) {
            var img, name = data.title, src = data.url;
            "rollover_tab" == $(".uk-tabs-panel > .uk-active").attr("id") ? $("input.uk-active", "#rollover_tab").or("#onmouseout").val(src) : ($("#alt").hasClass("uk-edited") || (name = (name = Wf.String.stripExt(name)).replace(/[-_]+/g, " "), 
            $("#alt").val(name)), $("#onmouseout").val(src), $("#src").val(src), 
            data.width && data.height ? $.each([ "width", "height" ], function(i, k) {
                var v = data[k] || "";
                $("#" + k).val(v).data("tmp", v).removeClass("uk-edited").addClass("uk-text-muted");
            }) : ((img = new Image()).onload = function() {
                $.each([ "width", "height" ], function(i, k) {
                    $("#" + k).val(img[k]).data("tmp", img[k]).removeClass("uk-edited").addClass("uk-text-muted");
                });
            }, img.src = src), name = Wf.sizeToFit({
                width: data.width,
                height: data.height
            }, {
                width: 80,
                height: 60
            }), $("#sample").attr({
                src: data.preview
            }).attr(name));
        }
    };
    window.ImageManagerDialog = ImageManagerDialog, $(document).ready(function() {
        ImageManagerDialog.init();
    });
}(jQuery);