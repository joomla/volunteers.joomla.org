/* jce - 2.9.99.5 | 2026-06-03 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, DOM = tinymce.DOM, counter = 0;
    tinymce.PluginManager.add("spellchecker", function(ed, url) {
        var self = this, languages = (self.menu = null, self.native_spellchecker = "browser" == ed.getParam("spellchecker_engine", "browser"), 
        self.native_spellchecker && ed.getParam("spellchecker_suggestions", !0) && ed.onContextMenu.addToTop(function(ed, e) {
            if (self.active) return !1;
        }), ed.addCommand("mceSpellCheck", function() {
            self.native_spellchecker ? (ed.getBody().spellcheck = self.active = !self.active, 
            ed.focus()) : self.active ? done() : (ed.setProgressState(1), sendRPC("checkWords", [ self.selectedLang, function() {
                var wl = [], tx = "", lo = {}, rawWords = [];
                walk(ed.getBody(), function(n) {
                    3 == n.nodeType && (tx += n.nodeValue + " ");
                }), rawWords = ed.getParam("spellchecker_word_pattern") ? tx.match("(" + ed.getParam("spellchecker_word_pattern") + ")", "gi") : (tx = tx.replace(new RegExp("([0-9]|[" + getSeparators() + "])", "g"), " "), 
                tx = tinymce.trim(tx.replace(/(\s+)/g, " ")), tx.split(" "));
                return each(rawWords, function(v) {
                    lo[v] || (wl.push(v), lo[v] = 1);
                }), wl;
            }() ], function(r) {
                0 < r.length ? (self.active = 1, function(wl) {
                    var dom = ed.dom, doc = ed.getDoc(), se = ed.selection, r = se.getRng(!0), nl = [], wl = wl.join("|"), re = getSeparators(), rx = new RegExp("(^|[" + re + "])(" + wl + ")(?=[" + re + "]|$)", "g");
                    walk(ed.getBody(), function(n) {
                        3 == n.nodeType && nl.push(n);
                    }), each(nl, function(n) {
                        var node, elem, txt, pos, v = n.nodeValue;
                        if (rx.lastIndex = 0, rx.test(v)) {
                            if (v = dom.encode(v), elem = dom.create("span", {
                                class: "mce-item-hidden"
                            }), tinymce.isIE) {
                                for (v = v.replace(rx, "$1<mcespell>$2</mcespell>"); -1 != (pos = v.indexOf("<mcespell>")); ) (txt = v.substring(0, pos)).length && (node = doc.createTextNode(dom.decode(txt)), 
                                elem.appendChild(node)), pos = (v = v.substring(pos + 10)).indexOf("</mcespell>"), 
                                txt = v.substring(0, pos), v = v.substring(pos + 11), 
                                elem.appendChild(dom.create("span", {
                                    class: "mce-item-hiddenspellword"
                                }, txt));
                                v.length && (node = doc.createTextNode(dom.decode(v)), 
                                elem.appendChild(node));
                            } else elem.innerHTML = v.replace(rx, '$1<span class="mce-item-hiddenspellword">$2</span>');
                            dom.replace(elem, n);
                        }
                    }), se.setRng(r);
                }(r), ed.setProgressState(0), ed.nodeChanged()) : (ed.setProgressState(0), 
                ed.getParam("spellchecker_report_no_misspellings", !0) && ed.windowManager.alert("spellchecker.no_mpell"));
            }));
        }), ed.onNodeChange.add(function(ed, cm) {
            cm.setActive("spellchecker", !!self.active);
        }), self.native_spellchecker || (ed.getParam("spellchecker_suggestions", !0) && (ed.onClick.add(showMenu, self), 
        ed.onContextMenu.add(showMenu, self)), ed.onBeforeGetContent.add(function() {
            self.active && removeWords();
        }), ed.onNodeChange.add(function(ed, cm) {
            cm.setActive("spellchecker", !!self.active);
        }), ed.onSetContent.add(function() {
            done();
        }), ed.onBeforeGetContent.add(function() {
            done();
        }), ed.onBeforeExecCommand.add(function(ed, cmd) {
            "mceFullScreen" == cmd && done();
        })), {});
        function updateMenu(mi) {
            mi.setSelected(1), this.selectedItem.setSelected(0), this.selectedItem = mi;
        }
        function walk(n, f) {
            var w, d = ed.getDoc();
            if (d.createTreeWalker) for (w = d.createTreeWalker(n, NodeFilter.SHOW_TEXT, null, !1); null != (n = w.nextNode()); ) f.call(this, n); else tinymce.walk(n, f, "childNodes");
        }
        function getSeparators() {
            for (var re = "", str = ed.getParam("spellchecker_word_separator_chars", '\\s!"#$%&()*+,-./:;<=>?@[]^_{|}\xdf\xa9\xb4\xc6\xb1\u2202\u2211\u220f\xaa\xba\u03a9\xe6\xf8\u25ca\u02dc\xa7\u201d\u201c'), i = 0; i < str.length; i++) re += "\\" + str.charAt(i);
            return re;
        }
        function removeWords(w) {
            var dom = ed.dom, se = ed.selection, r = se.getRng(!0);
            each(dom.select("span").reverse(), function(n) {
                !n || !dom.hasClass(n, "mce-item-hiddenspellword") && !dom.hasClass(n, "mce-item-hidden") || w && dom.decode(n.innerHTML) != w || dom.remove(n, 1);
            }), se.setRng(r);
        }
        function showMenu(ed, e) {
            var p1, m = self.menu, dom = ed.dom, vp = dom.getViewPort(ed.getWin()), wordSpan = e.target;
            if (e = 0, m || (m = ed.controlManager.createDropMenu("spellcheckermenu", {
                keyboard_focus: !0
            }), self.menu = m), dom.hasClass(wordSpan, "mce-item-hiddenspellword")) return m.removeAll(), 
            m.add({
                title: "spellchecker.wait",
                class: "mceMenuItemTitle"
            }).setDisabled(1), sendRPC("getSuggestions", [ self.selectedLang, dom.decode(wordSpan.innerHTML) ], function(r) {
                var ignoreRpc;
                m.removeAll(), 0 < r.length ? (m.add({
                    title: "spellchecker.sug",
                    class: "mceMenuItemTitle"
                }).setDisabled(1), each(r, function(v) {
                    m.add({
                        title: v,
                        onclick: function() {
                            dom.replace(ed.getDoc().createTextNode(v), wordSpan), 
                            checkDone();
                        }
                    });
                })) : m.add({
                    title: "spellchecker.no_sug",
                    class: "mceMenuItemTitle"
                }).setDisabled(1), ed.getParam("show_ignore_words", !0) && (m.addSeparator(), 
                ignoreRpc = ed.getParam("spellchecker_enable_ignore_rpc", ""), m.add({
                    title: "spellchecker.ignore_word",
                    onclick: function() {
                        var word = wordSpan.innerHTML;
                        dom.remove(wordSpan, 1), checkDone(), ignoreRpc && (ed.setProgressState(1), 
                        sendRPC("ignoreWord", [ self.selectedLang, word ], function(r) {
                            ed.setProgressState(0);
                        }));
                    }
                }), m.add({
                    title: "spellchecker.ignore_words",
                    onclick: function() {
                        var word = wordSpan.innerHTML;
                        removeWords(dom.decode(word)), checkDone(), ignoreRpc && (ed.setProgressState(1), 
                        sendRPC("ignoreWords", [ self.selectedLang, word ], function(r) {
                            ed.setProgressState(0);
                        }));
                    }
                })), ed.getParam("spellchecker_enable_learn_rpc") && m.add({
                    title: "spellchecker.learn_word",
                    onclick: function() {
                        var word = wordSpan.innerHTML;
                        dom.remove(wordSpan, 1), checkDone(), ed.setProgressState(1), 
                        sendRPC("learnWord", [ self.selectedLang, word ], function(r) {
                            ed.setProgressState(0);
                        });
                    }
                }), m.update();
            }), p1 = DOM.getPos(ed.getContentAreaContainer()), m.settings.offset_x = p1.x, 
            m.settings.offset_y = p1.y, ed.selection.select(wordSpan), p1 = dom.getPos(wordSpan), 
            m.showMenu(p1.x, p1.y + wordSpan.offsetHeight - vp.y), tinymce.dom.Event.cancel(e);
            m.hideMenu();
        }
        function checkDone() {
            var o, dom = ed.dom;
            each(dom.select("span"), function(n) {
                if (n && dom.hasClass(n, "mce-item-hiddenspellword")) return !(o = !0);
            }), o || done();
        }
        function done() {
            var la = this.active;
            this.active && (removeWords(), this.menu && this.menu.hideMenu(), la && ed.nodeChanged(), 
            this.active = !1);
        }
        function sendRPC(m, p, cb) {
            m = {
                id: function() {
                    for (var guid = new Date().getTime().toString(32), i = 0; i < 5; i++) guid += Math.floor(65535 * Math.random()).toString(32);
                    return "wf_" + guid + (counter++).toString(32);
                }(),
                method: m,
                params: p
            };
            tinymce.util.XHR.send({
                url: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.rpc&plugin=spellchecker&" + ed.settings.query,
                data: "json=" + JSON.stringify(m),
                content_type: "application/x-www-form-urlencoded",
                success: function(o) {
                    var c, e;
                    try {
                        c = JSON.parse(o);
                    } catch (e) {
                        c = {
                            error: "JSON Parse error"
                        };
                    }
                    !c || c.error ? (ed.setProgressState(0), e = c.error || "JSON Parse error", 
                    ed.windowManager.alert(e.errstr || "Error response: " + e)) : cb.call(self, c.result || "");
                },
                error: function(x) {
                    ed.setProgressState(0), ed.windowManager.alert("Error response: " + x);
                }
            });
        }
        each(ed.getParam("spellchecker_languages", "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv", "hash"), function(v, k) {
            0 === k.indexOf("+") && (k = k.substring(1), self.selectedLang = v), 
            languages[k] = v;
        }), ed.onPreInit.add(function() {
            self.native_spellchecker && ed.getParam("spellchecker_browser_state", 0) && (ed.settings.browser_spellcheck = !0, 
            self.active = !0);
        }), this.createControl = function(n, cm) {
            var self = this;
            if ("spellchecker" == n) return self.native_spellchecker ? cm.createButton(n, {
                title: "spellchecker.desc",
                cmd: "mceSpellCheck",
                scope: self
            }) : ((cm = cm.createSplitButton(n, {
                title: "spellchecker.desc",
                cmd: "mceSpellCheck",
                scope: self
            })).onRenderMenu.add(function(c, m) {
                m.add({
                    title: "spellchecker.langs",
                    class: "mceMenuItemTitle"
                }).setDisabled(1), self.menuItems = {}, each(languages, function(v, k) {
                    var mi, o = {
                        onclick: function() {
                            v != self.selectedLang && (updateMenu(mi), self.selectedLang = v);
                        }
                    };
                    o.title = k, (mi = m.add(o)).setSelected(v == self.selectedLang), 
                    self.menuItems[v] = mi, v == self.selectedLang && (self.selectedItem = mi);
                });
            }), cm);
        }, this.setLanguage = function(lang) {
            if (lang != this.selectedLang) {
                if (0 === tinymce.grep(this.languages, function(v) {
                    return v === lang;
                }).length) throw "Unknown language: " + lang;
                this.selectedLang = lang, this.menuItems && updateMenu(this.menuItems[lang]), 
                this.active && done();
            }
        };
    });
}();