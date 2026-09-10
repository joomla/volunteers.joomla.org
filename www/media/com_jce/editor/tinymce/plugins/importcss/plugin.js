/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, DOM = tinymce.DOM, PreviewCss = tinymce.util.PreviewCss;
    var rgba = {}, luma = {};
    function getLuminance(val) {
        var RsRGB, GsRGB, col;
        return luma[val] || (RsRGB = (col = function(val) {
            var r, b, g, a, values, match;
            return rgba[val] || (g = b = r = 0, -(a = 1) !== val.indexOf("#") ? (3 === (val = val.substr(1)).length && (val += val), 
            r = parseInt(val.substring(0, 2), 16), g = parseInt(val.substring(2, 4), 16), 
            b = parseInt(val.substring(4, 6), 16), 6 < val.length && (a = +((a = parseInt(val.substring(6, 8), 16)) / 255).toFixed(2))) : (val = val.replace(/\s/g, ""), 
            (values = (match = /^(?:rgb|rgba)\(([^\)]*)\)$/.exec(val)) ? match[1].split(",").map(function(x, i) {
                return parseFloat(x);
            }) : values) && (r = values[0], g = values[1], b = values[2], 4 === values.length) && (a = values[3] || 1)), 
            rgba[val] = {
                r: r,
                g: g,
                b: b,
                a: a
            }), rgba[val];
        }(val)).r / 255, GsRGB = col.g / 255, col = col.b / 255, RsRGB = RsRGB <= .03928 ? RsRGB / 12.92 : Math.pow((.055 + RsRGB) / 1.055, 2.4), 
        GsRGB = GsRGB <= .03928 ? GsRGB / 12.92 : Math.pow((.055 + GsRGB) / 1.055, 2.4), 
        col = col <= .03928 ? col / 12.92 : Math.pow((.055 + col) / 1.055, 2.4), 
        luma[val] = .2126 * RsRGB + .7152 * GsRGB + .0722 * col), luma[val];
    }
    function isReadable(color1, color2, wcag2, limit) {
        color1 = getLuminance(color1), color2 = getLuminance(color2), color1 = (Math.max(color1, color2) + .05) / (Math.min(color1, color2) + .05);
        return wcag2 = wcag2 || 4.5, limit = limit || 21, color1 >= parseFloat(wcag2) && color1 < parseFloat(limit);
    }
    tinymce.PluginManager.add("importcss", function(ed, url) {
        var self = this;
        function setGuideLinesColor() {
            var gray = [ "#000000", "#080808", "#101010", "#181818", "#202020", "#282828", "#303030", "#383838", "#404040", "#484848", "#505050", "#585858", "#606060", "#686868", "#696969", "#707070", "#787878", "#808080", "#888888", "#909090", "#989898", "#a0a0a0", "#a8a8a8", "#a9a9a9", "#b0b0b0", "#b8b8b8", "#bebebe", "#c0c0c0", "#c8c8c8", "#d0d0d0", "#d3d3d3", "#d8d8d8", "#dcdcdc", "#e0e0e0", "#e8e8e8", "#f0f0f0", "#f5f5f5", "#f8f8f8", "#ffffff" ], blue = [ "#0d47a1", "#1565c0", "#1976d2", "#1e88e5", "#2196f3", "#42a5f5", "#64b5f6", "#90caf9", "#bbdefb", "#e3f2fd" ], guidelines = "#787878", control = "#1e88e5", bodybg = ed.dom.getStyle(ed.getBody(), "background-color", !0), color = ed.dom.getStyle(ed.getBody(), "color", !0);
            if (bodybg) {
                ed._hasGuidelines = !0;
                for (var i = 0; i < gray.length; i++) if (isReadable(gray[i], bodybg, 4.5, 5) && ed.dom.toHex(color) !== ed.dom.toHex(gray[i])) {
                    guidelines = gray[i];
                    break;
                }
                for (var css, i = 0; i < blue.length; i++) if (isReadable(blue[i], bodybg, 4.5, 5)) {
                    control = blue[i];
                    break;
                }
                (guidelines || control) && (css = ":root{", guidelines && (css = css + "--mce-guidelines: " + guidelines + ";--mce-visualchars: #a8a8a8;"), 
                css += "--mce-placeholder: #efefef;", control && (css = css + "--mce-control-selection: " + control + ";--mce-control-selection-bg: #b4d7ff;"), 
                ed.dom.addStyle(css += "}"));
            }
        }
        ed.onImportCSS = new tinymce.util.Dispatcher(), ed.onImportCSS.add(function() {
            tinymce.is(ed.settings.importcss_classes) || self.get();
        }), ed.onInit.add(function() {
            var hex, bodybg, color;
            ed.onImportCSS.dispatch(), "auto" !== ed.settings.content_style_reset || ed.dom.hasClass(ed.getBody(), "mceContentReset") || (bodybg = ed.dom.getStyle(ed.getBody(), "background-color", !0), 
            color = ed.dom.getStyle(ed.getBody(), "color", !0), bodybg && color && ((hex = ed.dom.toHex(bodybg)) == ed.dom.toHex(color) && "#000000" === hex || isReadable(color, bodybg, 3) || ed.dom.addClass(ed.getBody(), "mceContentReset"))), 
            setGuideLinesColor();
        }), ed.onFocus.add(function(ed) {
            ed._hasGuidelines || setGuideLinesColor();
        }), this.get = function() {
            var self = this, doc = ed.getDoc(), href = "", rules = [], fontface = [], filtered = {}, classes = [];
            var bodyRx = !!ed.settings.body_class && new RegExp(".(" + ed.settings.body_class.split(" ").join("|") + ")");
            function parseCSS(stylesheet) {
                each(stylesheet.imports, function(r) {
                    var v;
                    0 < r.href.indexOf("://fonts.googleapis.com") ? (v = "@import url(" + r.href + ");", 
                    -1 === self.fontface.indexOf(v) && self.fontface.unshift(v)) : parseCSS(r);
                });
                try {
                    if (rules = stylesheet.cssRules || stylesheet.rules, !(href = stylesheet.href)) return;
                    if (url = href, -1 !== /\/(tinymce|plugins\/jce)\//.match(url) && -1 !== url.indexOf("content.css")) return;
                    href = href.substr(0, href.lastIndexOf("/") + 1), ed.hasStyleSheets = !0;
                } catch (e) {}
                var url;
                each(rules, function(r) {
                    switch (r.type || 1) {
                      case 1:
                        if (!function(href) {
                            var styleselect = ed.getParam("styleselect_stylesheets");
                            return !styleselect || (void 0 === filtered[href] && (filtered[href] = -1 !== href.indexOf(styleselect)), 
                            filtered[href]);
                        }(stylesheet.href)) return !0;
                        r.selectorText && each(r.selectorText.split(","), function(v) {
                            var value;
                            v = v.trim(), /\.mce[-A-Za-z0-9]/.test(v) || /\.wf[e]?-/.test(v) || (value = v, 
                            bodyRx && bodyRx.test(value)) || /\.[\w\-\:]+$/.test(v) && classes.push(v);
                        });
                        break;

                      case 3:
                        0 < r.href.indexOf("//fonts.googleapis.com") && (v = "@import url(" + r.href + ");", 
                        -1 === fontface.indexOf(v)) && fontface.unshift(v), -1 === r.href.indexOf("//") && parseCSS(r.styleSheet);
                        break;

                      case 5:
                        var v;
                        r.cssText && !1 === /(fontawesome|glyphicons|icomoon)/i.test(r.cssText) && (u = r.cssText, 
                        p = href, v = u.replace(/url\(["']?(.+?)["']?\)/gi, function(a, b) {
                            return b.indexOf("://") < 0 ? 'url("' + p + b + '")' : a;
                        }), -1 === fontface.indexOf(v)) && fontface.push(v);
                    }
                    var u, p;
                });
            }
            if (!classes.length) try {
                each(doc.styleSheets, function(styleSheet) {
                    parseCSS(styleSheet);
                });
            } catch (ex) {}
            if (!fontface.length) try {
                var setCss, head = DOM.doc.getElementsByTagName("head")[0], style = DOM.create("style", {
                    type: "text/css"
                }), css = self.fontface.join("\n");
                style.styleSheet ? (setCss = function() {
                    try {
                        style.styleSheet.cssText = css;
                    } catch (e) {}
                }, style.styleSheet.disabled ? setTimeout(setCss, 10) : setCss()) : style.appendChild(DOM.doc.createTextNode(css)), 
                head.appendChild(style);
            } catch (e) {}
            if (classes.length) return classes = classes.filter(function(val, ind, arr) {
                return arr.indexOf(val) === ind;
            }), ed.getParam("styleselect_sort", 1) && classes.sort(), ed.settings.importcss_classes = tinymce.map(classes, function(val) {
                var selectorText = (selectorText = /^(?:([a-z0-9\-_]+))?(\.[a-z0-9_\-\.]+)$/i.exec(selectorText = val)) && "body" !== selectorText[1] ? selectorText[2].substr(1).split(".").join(" ") : "", style = PreviewCss.getCssText(ed, {
                    classes: selectorText.split(" ")
                });
                return {
                    selector: val,
                    class: selectorText,
                    style: style
                };
            }), PreviewCss.reset(), ed.settings.importcss_classes;
        };
    });
}();