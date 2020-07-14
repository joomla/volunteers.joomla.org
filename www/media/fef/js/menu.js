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

akeeba.fef.menuButton = function(selector)
{
    // Use the default selector, if necessary
    if ((typeof selector === 'undefined') || (selector === ''))
    {
        selector = 'a.akeeba-menu-button';
    }

    var menuButtonsList = document.querySelectorAll(selector);

    if (menuButtonsList.length === 0)
    {
        return;
    }

    menuButtonsList.forEach(function(elButton) {
        elButton.addEventListener('click', function(event) {
            var elNav = elButton.parentElement.parentElement.querySelector('nav');

            if (elNav.style.display !== 'flex')
            {
                elNav.style.display = 'flex';

                return;
            }

            elNav.style.display = null;
        });
    });
};
