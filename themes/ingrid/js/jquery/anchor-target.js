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
(function () {

    "use strict";

    $.fn.anchorTarget = function (options) {

        function activate(elem) {
          elem.addClass('is-active')
        }

        function deactivate(elem) {
          elem.removeClass('is-active')
        }

        $.urlParam = function(name){
          var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
          if (results == null){
             return null;
          }
          else {
             return decodeURI(results[1]) || 0;
          }
        }

        return this.each(function () {

            var locationHash = window.location.hash.substr(1);
            var key = $(this)[0].dataset.key;
            if(key) {
              locationHash = $.urlParam(key);
            }
            var elemHash = $(this)[0].hash.substr(1);

            if(locationHash) {
              deactivate($(this));
            }
            if (locationHash === elemHash) {
              if(key) {
                activate($(this));
              }
              $('a[name=' + elemHash + ']').addClass('is-active');
            }
        });
    }

})();

$('.js-anchor-target').on('click', function (event) {
  var parent = $(this).parent();
  if(parent) {
    var children = parent.children();
    for (var i = 0; i < children.length; i++) {
      var child = $(children[i]);
      var hash = child[0].hash;
      if(hash) {
        $('a[name=' + child[0].hash.replace('#', '') + ']').removeClass('is-active');
        window.location.hash = hash;
        $('a[name=' + $(this)[0].hash.replace('#', '') + ']').addClass('is-active');
      }
    }
  }
});

$('.js-anchor-target-entry').on('click', function (event) {
  var topMenu = $(".nav-group");
  if(topMenu) {
    $('.anchor').removeClass('is-active');
    topMenu.find('.js-anchor-target-entry').removeClass("is-active");
  }
  $(this).addClass('is-active');
  $('a[name=' + $(this)[0].hash.replace('#', '') + ']').addClass('is-active');
});
