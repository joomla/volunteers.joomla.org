/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

window.addEventListener('DOMContentLoaded', (event) => {
    processScrollInit();

    if (typeof blockAdBlock === 'undefined') {
        adBlockDetected();
    } else {
        blockAdBlock.onDetected(adBlockDetected);
        blockAdBlock.on(true, adBlockDetected);
    }

    const selector = document.querySelectorAll('#adblock-msg .close');
    selector.forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            Cookies.set('joomla-adblock', 'closed', { expires: 30, domain: 'joomla.org' });
        });
    });

    function adBlockDetected() {
        document.getElementById('adblock-msg').classList.remove('d-none');

        if (Cookies.get('joomla-adblock') === 'closed') {
            document.getElementById('adblock-msg').classList.add('d-none');
        }
    }

    function processScrollInit() {
        // This width corresponds to the sm width in bootstrap css at which point the nav becomes sticky
        if (document.body.clientWidth > 575) {
            const subnav = document.querySelector('.subnav-wrapper');
            subnav.style.top = `${document.getElementById('mega-menu').offsetHeight}px`
        }
    }
});
