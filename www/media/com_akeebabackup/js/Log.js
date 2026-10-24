/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

// Object initialisation
if (typeof akeebabackup === "undefined")
{
    var akeebabackup = {};
}

if (typeof akeebabackup.Log === "undefined")
{
    akeebabackup.Log = {};
}

akeebabackup.Log.onShowBigLog = function ()
{
    var iFrameHolder           = document.getElementById("iframe-holder");
    var iFrameSource           = Joomla.getOptions("akeeba.Log.iFrameSrc");
    iFrameHolder.style.display = "block";
    iFrameHolder.insertAdjacentHTML(
        "beforeend",
        "<iframe width=\"99%\" src=\"" + iFrameSource + "\" height=\"400px\"/>"
    );
    this.parentNode.style.display = "none";
};

akeebabackup.System.documentReady(function ()
{
    akeebabackup.System.addEventListener("showlog", "click", akeebabackup.Log.onShowBigLog);
    akeebabackup.System.addEventListener("comAkeebaLogTagSelector", "change", function ()
    {
        document.forms.adminForm.submit();
    })
});