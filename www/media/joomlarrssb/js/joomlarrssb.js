/**
 * Ridiculously Responsive Social Sharing Buttons for joomla.org
 *
 * Plain JavaScript replacement for the jQuery rrssb.js: gives the buttons an
 * equal width, switches to the large or tiny format depending on the space
 * and opens the share links in a centered popup.
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */
(() => {
    const layout = () => {
        document.querySelectorAll('.rrssb-buttons').forEach((list) => {
            const buttons = [...list.children].filter((button) => button.offsetParent !== null);

            buttons.forEach((button) => {
                button.style.width = `${100 / buttons.length}%`;
            });

            const buttonWidth = buttons.length ? buttons[0].getBoundingClientRect().width : 0;

            list.classList.toggle('large-format', buttonWidth > 170);
            list.classList.toggle('tiny-format', list.getBoundingClientRect().width < 200);
        });
    };

    const popupCenter = (url, title, width, height) => {
        const left = (window.innerWidth / 2) - (width / 2) + window.screenLeft;
        const top = (window.innerHeight / 3) - (height / 3) + window.screenTop;
        const popup = window.open(url, title, `scrollbars=yes,width=${width},height=${height},top=${top},left=${left}`);

        if (popup) {
            popup.focus();
        }

        return popup;
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.rrssb-buttons a.popup');

        if (!link) {
            return;
        }

        const title = link.querySelector('.rrssb-text')?.textContent || '';

        // Fall back to the normal link when popups are blocked
        if (popupCenter(link.href, title, 580, 470)) {
            event.preventDefault();
        }
    });

    let resizeTimer;

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(layout, 100);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', layout);
    } else {
        layout();
    }
})();
