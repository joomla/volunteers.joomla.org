/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

jQuery(document).ready(function ($) {
    var navTop,
        isFixed = false;

    $('.hasTooltip').tooltip();
    processScrollInit();
    processScroll();

    if (typeof blockAdBlock === 'undefined') {
        adBlockDetected();
    } else {
        blockAdBlock.onDetected(adBlockDetected);
        blockAdBlock.on(true, adBlockDetected);
    }

    $('#adblock-msg .close').click(function (e) {
        e.preventDefault();
        Cookies.set('joomla-adblock', 'closed', { expires: 30, domain: 'joomla.org' });
    });

    function adBlockDetected() {
        $('#adblock-msg').removeClass('hide');

        if (Cookies.get('joomla-adblock') === 'closed') {
            $('#adblock-msg').addClass('hide');
        }
    }

    function processScrollInit() {
        if ($('.subnav-wrapper').length) {
            navTop = $('.subnav-wrapper').length && $('.subnav-wrapper').offset().top - 30;

            // Fix the container top
            $('.body .container-main').css('top', $('.subnav-wrapper').height() + $('#mega-menu').height());

            // Only apply the scrollspy when the toolbar is not collapsed
            if (document.body.clientWidth > 480) {
                $('.subnav-wrapper').height($('.subnav').outerHeight());
                $('.subnav').affix({
                    offset: {top: $('.subnav').offset().top - $('#mega-menu').height()}
                });
            }
        }
    }

    function processScroll() {
        if ($('.subnav-wrapper').length) {
            var scrollTop = $(window).scrollTop();
            if (scrollTop >= navTop && !isFixed) {
                isFixed = true;
                $('.subnav-wrapper').addClass('subhead-fixed');

                // Fix the container top
                $('.body .container-main').css('top', $('.subnav-wrapper').height() + $('#mega-menu').height());
            } else if (scrollTop <= navTop && isFixed) {
                isFixed = false;
                $('.subnav-wrapper').removeClass('subhead-fixed');
            }
        }
    }
});
