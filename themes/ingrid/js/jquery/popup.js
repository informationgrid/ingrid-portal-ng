/*
 * **************************************************-
 * InGrid Portal Base
 * ==================================================
 * Copyright (C) 2014 - 2023 wemove digital solutions GmbH
 * ==================================================
 * Licensed under the EUPL, Version 1.1 or – as soon they will be
 * approved by the European Commission - subsequent versions of the
 * EUPL (the "Licence");
 * 
 * You may not use this work except in compliance with the Licence.
 * You may obtain a copy of the Licence at:
 * 
 * http://ec.europa.eu/idabc/eupl5
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions and
 * limitations under the Licence.
 * **************************************************#
 */
var popup = (function () {

    "use strict";

    function open(messageTitle, message, boxId) {
        var box = $('.help-popup');
        if (boxId) {
          box = $(boxId);
        } else {
          update(box, messageTitle, message);
        }

        box.addClass('is-open');

    }

    function close(boxId) {
        var box = $('.help-popup');
        if (boxId) {
          box = $(boxId);
        }
        box.removeClass('is-open');

    }

    function update(box, messageTitle, message) {
        box.find('.popup__title').html(messageTitle);
        box.find('.popup__content').html(message);
    }

    return {
        open: open,
        close: close
    }

})();


$('.js-popup').on('click', function (event) {
    event.preventDefault();
    popup.open($(this).data('title'), $(this).data('content'), $(this).data('box'));
});

$('.js-popup-close').on('click', function (event) {
    event.preventDefault();
    popup.close($(this).data('box'));
});
