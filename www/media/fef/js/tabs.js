/*
 * Akeeba Frontend Framework (FEF)
 *
 * @package   fef
 * @copyright (c) 2017-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * Created by Crystal Dionysopoulou for Akeeba Ltd, https://www.akeeba.com
 */

if (typeof akeeba === 'undefined')
{
    akeeba = {};
}

if (typeof akeeba.fef === 'undefined')
{
    akeeba.fef = {};
}

if (typeof akeeba.fef.forEach === 'undefined')
{
	akeeba.fef.forEach = function (array, callback, scope) {
		for (var i = 0; i < array.length; i++) {
			callback.call(scope, i, array[i]);
		}
	};
}

/**
 * Activates the tab interface for specific HTML elements
 *
 * @param  {String}  [selector]  CSS selector for the tab-set wrapper(s). Default: 'div.akeeba-tabs'
 */
akeeba.fef.tabs = function(selector)
{
    // Use the default selector, if necessary
    if ((typeof selector === 'undefined') || (selector === ''))
    {
        selector = 'div.akeeba-tabs';
    }

    var wrappersList = document.querySelectorAll(selector);

    if (wrappersList.length === 0)
    {
        return;
    }

    // Loop for every tab set matching the selector
    akeeba.fef.forEach(wrappersList, function(i, elWrapper) {
        // Sort the child elements of the selector to tabs (label) and panels (section)
        var tabList = elWrapper.querySelectorAll('label');
        var tabId = elWrapper.id;
        var tabPreselected = '';

        if (tabList.length === 0)
        {
            return;
        }

        if (tabId != '')
        {
            tabPreselected = window.sessionStorage.getItem('akeeba.tabs.' + tabId);
        }

        akeeba.fef.forEach(tabList, function(k, elTab) {
            if (elTab.parentElement !== elWrapper)
            {
                return;
            }

            elTab.addEventListener('click', function (event) {
                var selectedId = this.getAttribute('for');

                akeeba.fef.forEach(tabList, function(q, element) {
                    if (element.parentElement !== elWrapper)
                    {
                        return;
                    }

                    var currentClasses = element.className.split(' ');
                    var newClass = '';

                    for (var property in currentClasses)
                    {
                        if (!currentClasses.hasOwnProperty(property) || (currentClasses[property] === 'active'))
                        {
                            continue;
                        }

                        newClass += currentClasses[property] + ' ';
                    }

                    if (newClass.trim)
                    {
                        newClass = newClass.trim();
                    }

                    // If the 'for' attribute matches add back the "active" class
                    if (element.getAttribute('for') === selectedId)
                    {
						window.sessionStorage.setItem('akeeba.tabs.' + tabId, selectedId);
                        newClass += ' active';
                    }

                    element.className = newClass;
                });
            });
        });

		if (tabPreselected != '')
		{
			akeeba.fef.forEach(tabList, function(k, elTab) {
				if (elTab.parentElement !== elWrapper)
				{
					return;
				}

				if (elTab.getAttribute('for') == tabPreselected)
                {
					var clickEvent = new MouseEvent('click');
					elTab.dispatchEvent(clickEvent);
				}
			});
		}
    });
};
