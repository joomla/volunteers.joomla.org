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

if (typeof akeeba.fef.triggerEvent === 'undefined')
{
	akeeba.fef.triggerEvent = function (element, eventName)
	{
		if (typeof element === 'undefined')
		{
			return;
		}

		if (element === null)
		{
			return;
		}

		// Allow the passing of an element ID string instead of the DOM elem
		if (typeof element === "string")
		{
			element = document.getElementById(element);
		}

		if (typeof element !== 'object')
		{
			return;
		}

		if (!(element instanceof Element))
		{
			return;
		}

		// Use jQuery and be done with it!
		if (typeof window.jQuery === 'function')
		{
			window.jQuery(element).trigger(eventName);

			return;
		}

		// Internet Explorer way
		if (document.fireEvent && (typeof window.Event === 'undefined'))
		{
			element.fireEvent('on' + eventName);

			return;
		}

		// This works on Chrome and Edge but not on Firefox. Ugh.
		var event = null;

		event = document.createEvent("Event");
		event.initEvent(eventName, true, true);
		element.dispatchEvent(event);
	};
}

/**
 * Activates the dropdown interface for specific HTML elements
 *
 * @param  {String}  [selector]  CSS selector for the tab-set wrapper(s). Default: 'nav.akeeba-dropdown'
 */
akeeba.fef.dropdown = function(selector)
{
    // Use the default selector, if necessary
    if ((typeof selector === 'undefined') || (selector === ''))
    {
        selector = 'nav.akeeba-dropdown';
    }

    // Get all outer wrappers (NAV elements)
    var navList = document.querySelectorAll(selector);

    if (navList.length === 0)
    {
        return;
    }

    // Loop for the first button inside each wrapper (the NAV)
    akeeba.fef.forEach(navList, function(i, elNav) {
        // Get the first button inside the NAV wrapper. That's our drop-down toggle.
        var buttonList = elNav.querySelectorAll('button');

        if (buttonList.length === 0)
        {
            return;
        }

        var elButton = buttonList[0];

        // Add an event listener to toggle the "open" class whenever the button is clicked.
		elButton.addEventListener('click', function (event) {
		    var isOpen = false;
			var currentClasses = this.className.split(' ');
			var newClass = '';

			for (var property in currentClasses)
			{
				if (!currentClasses.hasOwnProperty(property))
                {
                    continue;
                }

                if (currentClasses[property] === 'open')
                {
                    isOpen = true;

                    continue;
                }

				newClass += currentClasses[property] + ' ';
			}

			if (newClass.trim)
			{
				newClass = newClass.trim();
			}

			if (!isOpen)
            {
                newClass += ' open';
            }

			this.className = newClass;

			event.preventDefault();
		});

		// Loop through all section > a elements in the NAV
        var linkList = elNav.querySelectorAll('section > a');

        if (linkList.length === 0)
        {
            return;
        }

		/**
		 * Clicking on each link will do two things:
		 * - It will set the clicked link's class to “current”
		 * - It will unset the “current” class on every other link
		 * - It will trigger a virtual click on the drop-down button (to close the drop-down).
		 */
		akeeba.fef.forEach(linkList, function(i, elLink) {
			elLink.addEventListener('click', function (event) {
				// Close the drop-down
				akeeba.fef.triggerEvent(elButton, 'click');

				// Remove the “current” class from all links
				akeeba.fef.forEach(linkList, function(i, element) {
					var currentClasses = element.className.split(' ');
					var newClass = '';

					for (var property in currentClasses)
					{
						if (!currentClasses.hasOwnProperty(property) || (currentClasses[property] === 'current'))
						{
							continue;
						}

						newClass += currentClasses[property] + ' ';
					}

					if (newClass.trim)
					{
						newClass = newClass.trim();
					}

					element.className = newClass;
				});

				// Add the “current” class to the current link
				this.className += ' current';

				event.preventDefault();
			});
		});
    });
};
