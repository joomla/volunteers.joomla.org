/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(tinymce, tinyMCEPopup, $) {
    function trimSize(size) {
        return (size = size.replace(/([0-9\.]+)(px|%|in|cm|mm|em|ex|pt|pc)/i, "$1$2")) ? size.replace(/px$/, "") : "";
    }
    function valueToHex(val) {
        return val && "#" !== val.charAt(0) ? "#" + val : val;
    }
    Wf.setStyles = function() {
        var key, ed = tinyMCEPopup.editor, $proxy = $("<div></div>"), proxy = $proxy.get(0), map = ($proxy.attr("style", $("#style").val()), 
        {
            "background-image": "backgroundimage",
            "border-spacing": "cellspacing",
            "border-collapse": "cellspacing",
            "vertical-align": "valign",
            "background-color": "bgcolor",
            float: "align",
            "text-align": "align"
        }), legacy = [ "align", "valign" ], border = ($.each([ "background-image", "width", "height", "border-spacing", "border-collapse", "vertical-align", "background-color", "text-align", "float" ], function(i, k) {
            var col, r, g, re, v = ed.dom.getStyle(proxy, k);
            return !("" != v && ($proxy.css(k, ""), "width" !== k && "height" !== k || (v = trimSize(v))) && ("background-image" === k && (v = v.replace(new RegExp("url\\(['\"]?([^'\"]*)['\"]?\\)", "gi"), "$1")), 
            "background-color" === k && (col = v, re = new RegExp("rgb\\s*\\(\\s*([0-9]+).*,\\s*([0-9]+).*,\\s*([0-9]+).*\\)", "gi"), 
            v = 3 == (re = col.replace(re, "$1,$2,$3").split(",")).length ? (r = parseInt(re[0], 10).toString(16), 
            g = parseInt(re[1], 10).toString(16), re = parseInt(re[2], 10).toString(16), 
            "#" + (1 == r.length ? "0" + r : r) + (1 == g.length ? "0" + g : g) + (1 == re.length ? "0" + re : re)) : col), 
            "border-spacing" === k && (v = trimSize(v)), "border-collapse" === k && "collapse" === v && (v = 0), 
            "float" !== k || "" === $("#align").val()) && (k = map[k] || k, !isHTML4 || -1 === $.inArray(k, legacy))) || void $("#" + k).val(v).trigger("change");
        }), "auto" === proxy.style.marginLeft && "auto" === proxy.style.marginRight && "" === $("#align").val() && ($("#align").val("center"), 
        $proxy.css({
            "margin-left": "",
            "margin-right": ""
        })), !1), initialStyles = {
            width: "medium",
            style: "none",
            color: "currentcolor"
        }, styles = ($.each([ "width", "color", "style" ], function(i, k) {
            var v = ed.dom.getStyle($proxy.get(0), "border-" + k);
            $.each([ "top", "right", "bottom", "left" ], function(i, n) {
                n = "border-" + n + "-" + k, n = ed.dom.getStyle($proxy.get(0), n);
                ("" !== (n = initialStyles[k] === v ? "" : n) || n !== v && "" !== v) && (v = ""), 
                "" !== n && (v = n);
            }), "" !== v && (border = !0, $proxy.css("border-" + k, "")), "width" == k && (v = /[0-9][a-z]/.test(v) ? parseInt(v, 10) : v), 
            "color" == k && v && "#" === (v = Wf.String.toHex(v)).charAt(0) && (v = v.substr(1)), 
            border && ($("#border").attr("checked", "checked").trigger("change"), 
            "width" === k && 0 == $('option[value="' + v + '"]', "#border_width").length && $("#border_width").append(new Option(v, v)), 
            $("#border_" + k).val(v).trigger("change"));
        }), ed.dom.parseStyle($proxy.attr("style")));
        for (key in styles) (0 <= key.indexOf("-moz-") || 0 <= key.indexOf("-webkit-") || "border-image" === key) && delete styles[key];
        $("#style").val(ed.dom.serializeStyle(styles));
    }, Wf.updateStyles = function() {};
    var isHTML4 = "html4" === tinyMCEPopup.editor.settings.schema, isHTML5 = "html5-strict" === tinyMCEPopup.editor.settings.schema, TableDialog = {
        settings: {},
        init: function() {
            var ed = tinyMCEPopup.editor, layout = tinyMCEPopup.getWindowArg("layout", "table");
            if (this.settings.file_browser || $("input.browser").removeClass("browser"), 
            Wf.init({
                classes: ed.getParam("table_classes_custom", [])
            }), "merge" == layout) return this.initMerge();
            switch (isHTML5 && $("#axis, #abbr, #scope, #summary, #char, #charoff, #tframe, #nowrap, #rules, #cellpadding, #cellspacing").each(function() {
                $(this).add('label[for="' + this.id + '"]').hide();
            }), layout) {
              case "table":
                this.initTable();
                break;

              case "cell":
                this.initCell();
                break;

              case "row":
                this.initRow();
            }
            $(".uk-form-controls select").datalist().trigger("datalist:update"), 
            $(".uk-datalist").trigger("datalist:update");
        },
        insert: function() {
            switch (tinyMCEPopup.getWindowArg("layout", "table")) {
              case "table":
                this.insertTable();
                break;

              case "cell":
                this.updateCells();
                break;

              case "row":
                this.updateRows();
                break;

              case "merge":
                this.merge();
            }
        },
        initMerge: function() {
            $("#numcols").val(tinyMCEPopup.getWindowArg("cols", 1)), $("#numrows").val(tinyMCEPopup.getWindowArg("rows", 1)), 
            $("#insert").button("option", "label", tinyMCEPopup.getLang("update", "Update", !0));
        },
        updateClasses: function(values) {
            values = values.replace(/(?:^|\s)mce-item-(\w+)(?!\S)/g, ""), values = $.trim(values), 
            $("#classes").val(values).trigger("change");
        },
        initTable: function() {
            var ed = tinyMCEPopup.editor, elm = ed.dom.getParent(ed.selection.getNode(), "table"), action = (action = tinyMCEPopup.getWindowArg("action")) || (elm ? "update" : "insert");
            if (isHTML4 && ($("#table_border").replaceWith('<input type="checkbox" id="table_border" value="" />'), 
            $("#table_border").on("click", function() {
                this.value = this.checked ? 1 : "";
            })), elm && "insert" != action) {
                for (var rowsAr = elm.rows, cols = 0, i = 0; i < rowsAr.length; i++) rowsAr[i].cells.length > cols && (cols = rowsAr[i].cells.length);
                $("#cols").val(cols), $("#rows").val(rowsAr.length), $("#caption").prop("checked", 0 < elm.getElementsByTagName("caption").length), 
                $.each([ "align", "width", "height", "cellpadding", "cellspacing", "id", "summary", "dir", "lang", "bgcolor", "background", "frame", "rules", "border" ], function(i, k) {
                    var v = ed.dom.getAttrib(elm, k);
                    if ("background" === k && "" !== v && (v = v.replace(new RegExp("url\\(['\"]?([^'\"]*)['\"]?\\)", "gi"), "$1")), 
                    "border" === k && "" !== v) return $("#table_border").val(function() {
                        return v = parseInt(v, 10), "checkbox" === this.type && (this.checked = !!v), 
                        v;
                    }), !0;
                    $("#" + k).val(v);
                }), this.updateClasses(ed.dom.getAttrib(elm, "class")), $("#style").val(ed.dom.getAttrib(elm, "style")).trigger("change"), 
                this.orgTableWidth = $("#width").val(), this.orgTableHeight = $("#height").val(), 
                $("#insert .uk-button-text").text(tinyMCEPopup.getLang("update", "Update", !0));
            } else Wf.setDefaults(this.settings.defaults);
            "update" == action && $("#cols, #rows").prop("disabled", !0), $(".uk-datalist").trigger("datalist:update");
        },
        initRow: function() {
            var self = this, ed = tinyMCEPopup.editor, dom = tinyMCEPopup.dom, elm = dom.getParent(ed.selection.getStart(), "tr"), selected = dom.select("td.mceSelected,th.mceSelected", elm), dom = elm.parentNode.nodeName.toLowerCase();
            $("#style").val(ed.dom.getAttrib(elm, "style")).trigger("change"), $.each([ "align", "width", "height", "cellpadding", "cellspacing", "id", "summary", "dir", "lang", "bgcolor", "background" ], function(i, k) {
                var v = ed.dom.getAttrib(elm, k), dv = $("#" + k).val();
                "background" === k && "" !== v && (v = v.replace(new RegExp("url\\(['\"]?([^'\"]*)['\"]?\\)", "gi"), "$1")), 
                "" !== dv && (v = dv), selected.length && -1 !== $.inArray(k, [ "bgcolor", "background", "height", "id", "lang", "align" ]) && (v = isHTML4 ? v : ""), 
                $("#" + k).val(v);
            }), this.updateClasses(ed.dom.getAttrib(elm, "class")), $("#rowtype").on("change", function() {
                self.setActionforRowType();
            }).val(dom).trigger("change"), 0 === selected.length ? $("#insert .uk-button-text").text(tinyMCEPopup.getLang("update", "Update", !0)) : $("#action").hide();
        },
        initCell: function() {
            var ed = tinyMCEPopup.editor, dom = ed.dom, elm = dom.getParent(ed.selection.getStart(), "td,th");
            dom.hasClass(elm, "mceSelected") ? $("#action").hide() : ($("#style").val(ed.dom.getAttrib(elm, "style")).trigger("change"), 
            $.each([ "align", "valign", "width", "height", "cellpadding", "cellspacing", "id", "summary", "dir", "lang", "bgcolor", "background", "scope" ], function(i, k) {
                var v = ed.dom.getAttrib(elm, k), dv = $("#" + k).val();
                "background" === k && "" !== v && (v = v.replace(new RegExp("url\\(['\"]?([^'\"]*)['\"]?\\)", "gi"), "$1")), 
                "" !== dv && (v = dv), $("#" + k).val(v);
            }), this.updateClasses(ed.dom.getAttrib(elm, "class")), $("#celltype").val(elm.nodeName.toLowerCase())), 
            $("#insert .uk-button-text").text(tinyMCEPopup.getLang("update", "Update", !0));
        },
        merge: function() {
            tinyMCEPopup.restoreSelection(), tinyMCEPopup.getWindowArg("onaction")({
                cols: $("#numcols").val(),
                rows: $("#numrows").val()
            }), tinyMCEPopup.close();
        },
        getStyles: function() {
            var styles, dom = tinyMCEPopup.editor.dom, style = $("#style").val();
            return isHTML4 || (styles = {
                "vertical-align": "",
                float: ""
            }, $.each([ "width", "height", "backgroundimage", "border", "bgcolor" ], function(i, k) {
                var v = $("#" + k).val();
                if ("backgroundimage" === k && ("" !== v && (v = 'url("' + v + '")'), 
                k = "background-image"), "bgcolor" === k && v && ("" !== v && (v = valueToHex(v)), 
                k = "background-color"), "width" !== k && "height" !== k || v && !/\D/.test(v) && (v = parseInt(v, 10) + "px"), 
                "border" === k) return $("#border").is(":checked") && $.each([ "width", "style", "color" ], function(i, n) {
                    var s = $("#border_" + n).val();
                    "width" !== n || "" === s || /\D/.test(s) || (s = parseInt(s, 10) + "px"), 
                    "color" === n && (s = valueToHex(s)), styles["border-" + n] = s;
                }), !0;
                styles[k] = v;
            }), style = dom.serializeStyle($.extend(dom.parseStyle(style), styles)), 
            style = dom.serializeStyle(dom.parseStyle(style))), style;
        },
        insertTable: function() {
            var ed = tinyMCEPopup.editor, dom = ed.dom, elm = (tinyMCEPopup.restoreSelection(), 
            ed.dom.getParent(ed.selection.getNode(), "table")), action = (action = tinyMCEPopup.getWindowArg("action")) || (elm ? "update" : "insert"), border = 0, html = "", width = $("#width").val(), height = $("#height").val(), align = $("#align").val(), cols = $("#cols").val(), rows = $("#rows").val(), cellpadding = $("#cellpadding").val(), cellspacing = $("#cellspacing").val(), frame = $("#tframe").val(), rules = $("#rules").val(), className = $("#classes").val(), id = $("#id").val(), summary = $("#summary").val(), dir = $("#dir").val(), lang = $("#lang").val(), caption = $("#caption").is(":checked"), bgColor = valueToHex($("#bgcolor").val()), style = this.getStyles(), border = $("#table_border").val();
            if ($("#table_border").is(":checkbox") && (border = $("#table_border").is(":checked") ? "1" : ""), 
            isHTML4 || (bgColor = height = width = align = ""), "update" == action) return ed.execCommand("mceBeginUndoLevel"), 
            isHTML5 || (dom.setAttrib(elm, "cellPadding", cellpadding, !0), dom.setAttrib(elm, "cellSpacing", cellspacing, !0)), 
            dom.setAttribs(elm, {
                width: width,
                height: height,
                align: align,
                border: border,
                bgColor: bgColor
            }), dom.setAttrib(elm, "frame", frame), dom.setAttrib(elm, "rules", rules), 
            dom.setAttrib(elm, "class", className), dom.setAttrib(elm, "style", style), 
            dom.setAttrib(elm, "id", id), dom.setAttrib(elm, "summary", summary), 
            dom.setAttrib(elm, "dir", dir), dom.setAttrib(elm, "lang", lang), (action = ed.dom.select("caption", elm)[0]) && !caption && action.parentNode.removeChild(action), 
            !action && caption && ((action = elm.ownerDocument.createElement("caption")).innerHTML = '<br data-mce-bogus="1"/>', 
            elm.insertBefore(action, elm.firstChild)), isHTML4 || $("#align").val() && ed.formatter.apply("align" + $("#align").val(), {}, elm), 
            ed.addVisual(), ed.nodeChanged(), ed.execCommand("mceEndUndoLevel", !1, {}, {
                skip_undo: !0
            }), ed.execCommand("mceRepaint"), tinyMCEPopup.close(), !0;
            html = (html += "<table") + this.makeAttrib("id", id), border && (html += this.makeAttrib("border", border)), 
            html = (html = (html = (html = (html = (html = (html = (html = (html = (html = (html = (html = (html = (html += this.makeAttrib("cellpadding", cellpadding)) + this.makeAttrib("cellspacing", cellspacing)) + this.makeAttrib("data-mce-new", "1")) + this.makeAttrib("width", width)) + this.makeAttrib("height", height)) + this.makeAttrib("align", align)) + this.makeAttrib("frame", frame)) + this.makeAttrib("rules", rules)) + this.makeAttrib("class", className)) + this.makeAttrib("style", style)) + this.makeAttrib("summary", summary)) + this.makeAttrib("dir", dir)) + this.makeAttrib("lang", lang)) + this.makeAttrib("bgcolor", bgColor) + ">", 
            caption && (html += '<caption><br data-mce-bogus="1" /></caption>');
            for (var patt, y = 0; y < rows; y++) {
                html += "<tr>";
                for (var x = 0; x < cols; x++) html += '<td><br data-mce-bogus="1" /></td>';
                html += "</tr>";
            }
            html += "</table>", ed.execCommand("mceBeginUndoLevel"), ed.settings.fix_table_elements ? (patt = "", 
            ed.focus(), ed.selection.setContent('<br class="_mce_marker" />'), tinymce.each("h1,h2,h3,h4,h5,h6,p".split(","), function(n) {
                patt && (patt += ","), patt += n + " ._mce_marker";
            }), tinymce.each(ed.dom.select(patt), function(n) {
                ed.dom.split(ed.dom.getParent(n, "h1,h2,h3,h4,h5,h6,p"), n);
            }), dom.setOuterHTML(dom.select("br._mce_marker")[0], html)) : ed.execCommand("mceInsertContent", !1, html), 
            tinymce.each(dom.select("table[data-mce-new]"), function(node) {
                var tdorth = dom.select("td,th", node);
                tinymce.isIE && !tinymce.isIE11 && null == node.nextSibling && (ed.settings.forced_root_block ? dom.insertAfter(dom.create(ed.settings.forced_root_block), node) : dom.insertAfter(dom.create("br", {
                    "data-mce-bogus": "1"
                }), node));
                try {
                    ed.selection.setCursorLocation(tdorth[0], 0);
                } catch (ex) {}
                isHTML4 || $("#align").val() && ed.formatter.apply("align" + $("#align").val(), {}, node), 
                dom.setAttrib(node, "data-mce-new", "");
            }), ed.addVisual(), ed.execCommand("mceEndUndoLevel", !1, {}, {
                skip_undo: !0
            }), tinyMCEPopup.close();
        },
        updateCell: function(td, skip_id) {
            var v, align, valign, width, height, bgColor, self = this, ed = tinyMCEPopup.editor, dom = ed.dom, doc = ed.getDoc(), curCellType = td.nodeName.toLowerCase(), celltype = $("#celltype").val(), cells = ed.dom.select("td.mceSelected,th.mceSelected");
            if (cells.length || cells.push(td), width = $("#width").val(), height = $("#height").val(), 
            align = $("#align").val(), valign = $("#valign").val(), bgColor = valueToHex($("#bgcolor").val()), 
            isHTML4 || (bgColor = height = width = valign = align = ""), dom.setAttribs(td, {
                width: width,
                height: height,
                bgColor: bgColor,
                align: align,
                valign: valign
            }), $.each([ "id", "lang", "dir", "classes", "scope", "style" ], function(i, k) {
                if (v = $("#" + k).val(), "id" === k && skip_id) return !0;
                var existingStyle, value;
                "style" === k && (v = self.getStyles(), 1 < cells.length) && (existingStyle = dom.parseStyle(dom.getAttrib(td, "style")), 
                v = dom.serializeStyle($.extend(existingStyle, dom.parseStyle(v)))), 
                existingStyle = td, k = k = "classes" === k ? "class" : k, value = v, 
                1 !== cells.length && "" === value || dom.setAttrib(existingStyle, k, value);
            }), isHTML4 || ($("#align").val() && ed.formatter.apply("align" + $("#align").val(), {}, td), 
            $("#valign").val() && ed.formatter.apply("valign" + $("#valign").val(), {}, td)), 
            curCellType != celltype) {
                for (var newCell = doc.createElement(celltype), c = 0; c < td.childNodes.length; c++) newCell.appendChild(td.childNodes[c].cloneNode(1));
                for (var a = 0; a < td.attributes.length; a++) ed.dom.setAttrib(newCell, td.attributes[a].name, ed.dom.getAttrib(td, td.attributes[a].name));
                td.parentNode.replaceChild(newCell, td), td = newCell;
            }
            return td;
        },
        updateCells: function() {
            var el, tdElm, trElm, tableElm, self = this, ed = tinyMCEPopup.editor, inst = ed;
            if (tinyMCEPopup.restoreSelection(), el = ed.selection.getStart(), tdElm = ed.dom.getParent(el, "td,th"), 
            trElm = ed.dom.getParent(el, "tr"), tableElm = ed.dom.getParent(el, "table"), 
            ed.dom.hasClass(tdElm, "mceSelected")) tinymce.each(ed.dom.select("td.mceSelected,th.mceSelected"), function(td) {
                self.updateCell(td);
            }); else switch (ed.execCommand("mceBeginUndoLevel"), $("#action").val()) {
              case "cell":
                var celltype = $("#celltype").val(), scope = $("#scope").val();
                if (ed.getParam("accessibility_warnings", 1)) return void ("th" == celltype && "" == scope ? tinyMCEPopup.confirm(ed.getLang("table_dlg.missing_scope", "Missing Scope", !0), doUpdate) : doUpdate(!0));
                this.updateCell(tdElm);
                break;

              case "row":
                var cell = trElm.firstChild;
                for ("TD" != cell.nodeName && "TH" != cell.nodeName && (cell = this.nextCell(cell)); cell = this.updateCell(cell, !0), 
                null != (cell = this.nextCell(cell)); );
                break;

              case "all":
                for (var rows = tableElm.getElementsByTagName("tr"), i = 0; i < rows.length; i++) {
                    cell = rows[i].firstChild;
                    for ("TD" != cell.nodeName && "TH" != cell.nodeName && (cell = this.nextCell(cell)); cell = this.updateCell(cell, !0), 
                    null != (cell = this.nextCell(cell)); );
                }
            }
            function doUpdate(state) {
                state && (self.updateCell(tdElm), ed.addVisual(), ed.nodeChanged(), 
                inst.execCommand("mceEndUndoLevel"), tinyMCEPopup.close());
            }
            ed.addVisual(), ed.nodeChanged(), inst.execCommand("mceEndUndoLevel"), 
            tinyMCEPopup.close();
        },
        updateRow: function(tr, skip_id, skip_parent) {
            var v, self = this, ed = tinyMCEPopup.editor, dom = ed.dom, doc = ed.getDoc(), curRowType = tr.parentNode.nodeName.toLowerCase(), rowtype = $("#rowtype").val(), rows = dom.getParent(ed.selection.getStart(), "table").rows;
            if (rows.length || rows.push(tr), dom.setAttribs(tr, {
                height: "",
                align: "",
                valign: "",
                bgColor: ""
            }), $.each([ "id", "lang", "dir", "classes", "scope", "style" ], function(i, k) {
                if (v = $("#" + k).val(), "id" === k && skip_id) return !0;
                var elm, value;
                "style" === k && (v = self.getStyles()), elm = tr, k = k = "classes" === k ? "class" : k, 
                value = v, 1 !== rows.length && !tinymce.is(value) || dom.setAttrib(elm, k, value);
            }), isHTML4 || $("#align").val() && ed.formatter.apply("align" + $("#align").val(), {}, tr), 
            curRowType != rowtype && !skip_parent) {
                for (var curRowType = tr.cloneNode(1), theTable = dom.getParent(tr, "table"), dest = rowtype, newParent = null, i = 0; i < theTable.childNodes.length; i++) theTable.childNodes[i].nodeName.toLowerCase() == dest && (newParent = theTable.childNodes[i]);
                null == newParent && (newParent = doc.createElement(dest), "thead" == dest ? "CAPTION" == theTable.firstChild.nodeName ? ed.dom.insertAfter(newParent, theTable.firstChild) : theTable.insertBefore(newParent, theTable.firstChild) : theTable.appendChild(newParent)), 
                newParent.appendChild(curRowType), tr.parentNode.removeChild(tr), 
                tr = curRowType;
                skip_parent = ed.dom.select("td", tr);
                tinymce.each(skip_parent, function(cell) {
                    ed.dom.rename(cell, "th");
                });
            }
        },
        updateRows: function() {
            var trElm, tableElm, self = this, ed = tinyMCEPopup.editor, dom = ed.dom, action = $("#action").val();
            if (tinyMCEPopup.restoreSelection(), trElm = dom.getParent(ed.selection.getStart(), "tr"), 
            tableElm = dom.getParent(ed.selection.getStart(), "table"), 0 < dom.select("td.mceSelected,th.mceSelected", trElm).length) tinymce.each(tableElm.rows, function(tr) {
                for (var i = 0; i < tr.cells.length; i++) if (dom.hasClass(tr.cells[i], "mceSelected")) return void self.updateRow(tr, !0);
            }); else switch (ed.execCommand("mceBeginUndoLevel"), action) {
              case "row":
                this.updateRow(trElm);
                break;

              case "all":
                for (var rows = tableElm.getElementsByTagName("tr"), i = 0; i < rows.length; i++) this.updateRow(rows[i], !0);
                break;

              case "odd":
              case "even":
                for (rows = tableElm.getElementsByTagName("tr"), i = 0; i < rows.length; i++) (i % 2 == 0 && "odd" == action || i % 2 != 0 && "even" == action) && this.updateRow(rows[i], !0, !0);
            }
            ed.addVisual(), ed.nodeChanged(), ed.execCommand("mceEndUndoLevel"), 
            tinyMCEPopup.close();
        },
        makeAttrib: function(attrib, value) {
            return "" == (value = void 0 !== value && null != value ? value : $("#" + attrib).val()) ? "" : " " + attrib + '="' + (value = (value = (value = (value = value.replace(/&/g, "&amp;")).replace(/\"/g, "&quot;")).replace(/</g, "&lt;")).replace(/>/g, "&gt;")) + '"';
        },
        nextCell: function(elm) {
            for (;null != (elm = elm.nextSibling); ) if ("TD" == elm.nodeName || "TH" == elm.nodeName) return elm;
            return null;
        },
        isCssSize: function(value) {
            return /^[0-9.]+(%|in|cm|mm|em|ex|pt|pc|px)$/.test(value);
        },
        cssSize: function(value, def) {
            return value = tinymce.trim(value || def), this.isCssSize(value) ? value : parseInt(value, 10) + "px";
        },
        setActionforRowType: function() {
            "tbody" === $("#rowtype").val() ? $("#action").prop("disabled", !1) : $("#action").val("row").prop("disabled", !0);
        }
    };
    tinyMCEPopup.onInit.add(TableDialog.init, TableDialog), window.TableDialog = TableDialog;
}(tinymce, tinyMCEPopup, jQuery);