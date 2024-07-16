/*
 * **************************************************-
 * InGrid Portal Base
 * ==================================================
 * Copyright (C) 2014 - 2024 wemove digital solutions GmbH
 * ==================================================
 * Licensed under the EUPL, Version 1.2 or – as soon they will be
 * approved by the European Commission - subsequent versions of the
 * EUPL (the "Licence");
 *
 * You may not use this work except in compliance with the Licence.
 * You may obtain a copy of the Licence at:
 *
 * https://joinup.ec.europa.eu/software/page/eupl
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions and
 * limitations under the Licence.
 * **************************************************#
 */
/* Logo
 *
 * Send a request for the icons.svg file and load it to the start of the DOM, under <body>.
 */
(function () {

    var client = new XMLHttpRequest();

    client.onreadystatechange = function () {
        if (client.readyState == 4 && client.status == 200) {
            if (client.responseText) {
                var first = document.body.firstChild;
                var div = document.createElement('div');
                div.innerHTML = client.responseText;

                document.body.insertBefore(div, first);
            }
        }
    }

    client.open('GET', '/user/themes/ingrid/images/template/icons.svg');
    client.send();

})();


/* Utilities
 */
(function () {

    window.transitionend = (function () {
        var t, el = document.createElement('fakeelement'),
                transitions = {
                    'transition': 'transitionend',
                    'OTransition': 'oTransitionEnd',
                    'MozTransition': 'transitionend',
                    'WebkitTransition': 'webkitTransitionEnd'
                }

        for (t in transitions) {
            if (el.style[t] !== undefined) {
                return transitions[t];
            }
        }
    })();

})();

/* Message Box
 *
 * Closes and deletes the messagebox.
 */
(function ($) {

    $('.js-message-close').on('click', function () {

        var box = $(this).parents('.message');

        if (!box.length) {
            return;
        }

        box.addClass('is-deleted');

        box.one(transitionend, function () {
            $(this).remove();
        });

    });

})(jQuery);


/* Sidebar Toggle
 */
(function ($) {

    var sidebar = $('.sidebar');
    var toggle = $(".js-sidebar-toggle");

    toggle.on('click', function () {
        toggle.toggleClass('is-active');
        sidebar.toggleClass('is-active');
    });

})(jQuery);


/* To Top Animation
 */
(function ($) {

    $(".js-top").on('click', function () {
        $("html, body").animate({scrollTop: 0}, 400);
        return false;
    });

})(jQuery);

/* Main Navigation toggles
 */
(function ($) {

    var active;

    $(".js-nav-desktop-toggle").on('click', function (event) {

        event.preventDefault();

        if (active != null && active !== this) {
            $(active).removeClass('is-active');
        }

        $(this).toggleClass('is-active');

        active = this;

    });

})(jQuery);

/* Header Toggle
 */
(function ($) {

    var toggles = $(".js-header-toggle");

    toggles.on('click', function (event) {

        event.preventDefault();

        toggles.not(this).removeClass('is-active');

        $(this).toggleClass('is-active');

    });

})(jQuery);

/* Mobile Navigation
 */
(function ($) {

    var page = $('.page');
    var nav = $('.nav-overlay');
    var header = $('.header');

    var hideNav = function () {
        nav.off($.ontransitionend, hideNav);
        nav.addClass('is-fixed');
    }

    var hidePage = function () {
        nav.off(transitionend, hidePage);
        header.addClass('is-hiding');
        page.addClass('is-fixed');
    }

    $('.js-nav-mobile-toggle').on('click', function () {

        $(this).toggleClass('is-active');

        // We have to use vanilla JS here because jQuery cannot currently manipulate classes on <svg> elements.
        var icons = this.querySelectorAll('.icon');
        for (var i = 0, j = icons.length; i < j; i++) {
            var icon = icons[i];
            if(icon.attributes["class"]){
                var iconClass = icon.attributes["class"].value.trim();
                if(iconClass.indexOf('ob-fade') > -1){
                    icon.attributes["class"].value = iconClass.replace('ob-fade', '');
                }else{
                    icon.attributes["class"].value = iconClass + ' ob-fade';
                }
            }
        }

        if (nav.hasClass('is-active')) {
            nav.on(transitionend, hideNav);
            page.removeClass('is-fixed is-hidden');
            header.removeClass('is-hiding');
            nav.removeClass('is-active');

        }
        else {
            nav.on(transitionend, hidePage);
            nav.removeClass('is-fixed').addClass('is-active');

        }

    });

})(jQuery);
