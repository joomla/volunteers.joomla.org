/* jce - 2.8.14 | 2020-06-19 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2020 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(tinymce){var VK=tinymce.VK;tinyMCE.onAddEditor.add(function(mgr,ed){tinymce.isMac&&tinymce.isGecko&&!tinymce.isIE11&&ed.onKeyDown.add(function(ed,e){!VK.metaKeyPressed(e)||e.shiftKey||37!=e.keyCode&&39!=e.keyCode||(ed.selection.getSel().modify("move",37==e.keyCode?"backward":"forward","word"),e.preventDefault())})})}(tinymce);