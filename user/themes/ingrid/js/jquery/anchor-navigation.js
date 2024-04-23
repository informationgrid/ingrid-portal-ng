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

    $.fn.anchorNavigation = function (options) {

        function activate(elem) {
          elem.addClass('is-active')
        }

        function deactivate(elem) {
          elem.removeClass('is-active')
        }

        return this.each(function () {

            var hasActiveItem = false;
            var children = $(this).find("div.accordion-content a");

            for ( var i = 0; i < children.length; i++) {
              var child = children.get(i);
              if ($(child).hasClass("is-active")) {
                hasActiveItem = true;
                activate($(child).closest("li.accordion-item"));
              }
            }
            
            if(!hasActiveItem && children.length > 0){
              activate($(children.get(0)).closest("li.accordion-item"));
            }
        });
    }

})();
