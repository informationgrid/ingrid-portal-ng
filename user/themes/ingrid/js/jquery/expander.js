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
var expander = (function () {

    "use strict";

    var expander_open = null;
    var expander_close = null;
    var expander_content = null;
    
    function expand(ident, isAll) {
        if(ident){
            expander_content = $('.js-expander-content.' + ident);
            expander_content.removeClass('is-hidden');
            expander_close = $('.js-expander-close.' + ident);
            expander_close.removeClass('is-hidden');
            expander_open = $('#' + ident +'.js-expander');
            expander_open.addClass('is-hidden');
            updateParams("more", ident, true, isAll);
        }
    }

    function close(ident, isAll) {
        if(ident){
          expander_content = $('.js-expander-content.' + ident);
          expander_close = $('.js-expander-close.' + ident);
          expander_open = $('#' + ident +'.js-expander');
          updateParams("more", ident, false, isAll);
        }
        expander_content.addClass('is-hidden');
        expander_close.addClass('is-hidden');
        expander_open.removeClass('is-hidden');
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

    function updateQueryStringParameter(uri, key, value) {
      var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
      var splitUrl = uri.split('#');
      var url = splitUrl[0];
      var hash = "";
      if(splitUrl.length > 1) {
        hash = "#" + splitUrl[1];
      }
      var separator = url.indexOf('?') !== -1 ? "&" : "?";
      if (url.match(re)) {
        return url.replace(re, '$1' + key + "=" + value + '$2') + hash;
      }
      else {
        return url + separator + key + "=" + value + hash;
      }
    }

    function updateParams(key, id , isAdd, isAll) {
      var paramKey = $.urlParam(key);
      if(paramKey) {
        var paramKeys = paramKey.split(',');
        if(paramKeys.indexOf(id) > -1){
          if(!isAdd) {
            paramKeys = paramKeys.filter(function(e) { return e !== id; });
            paramKeys = paramKeys.filter(function(e) { return e !== i + "_links"; });
          }
        } else {
          if(isAdd) {
            paramKeys.push(id);
          }
        }
        paramKey = ''
        for (var i = 0; i < paramKeys.length; i++) {
          if(i > 0) {
            paramKey = paramKey + ',';
          }
          paramKey = paramKey + paramKeys[i];
        }
      } else {
        paramKey = id
      }
      var url = "";
      if(paramKey === '') {
        url = updateQueryStringParameter(window.location.href, key, paramKey);
      } else {
        if(isAll && isAdd) {
          url = updateQueryStringParameter(window.location.href, key, paramKey);
        } else if(!isAll) {
          url = updateQueryStringParameter(window.location.href, key, paramKey);
        } else {
          url = updateQueryStringParameter(window.location.href, key, "");
        }
      }
      var urlSplit = url.split("?");
      
      history.replaceState(null, null, "?"+urlSplit[urlSplit.length - 1]);
    }

    return {
        open: expand,
        close: close
    }

})();


$('.js-expander').on('click', function (event) {
    event.preventDefault();
    expander.open(this.id, false);
    $(this).parent().addClass("is-active");
});

$('.js-expander-close').on('click', function (event) {
    event.preventDefault();
    var id = $(event.currentTarget).prevUntil(event.currentTarget,".js-expander")[0].id;
    expander.close(id, false);
    $(this).parent().removeClass("is-active");
});

$('.js-toggle-all-expander-collapse').on('click', function (event) {
  event.preventDefault();
  $(this).addClass("is-active").siblings().removeClass('is-active');
  var jsExpanders = $('.data .teaser-data > .js-expander');
  for (var i = 0; i < jsExpanders.length; i++) {
    var jsExpander = jsExpanders.get(i);
    expander.close(jsExpander.id, true);
 }
});

$('.js-toggle-all-expander-expand').on('click', function (event) {
  event.preventDefault();
   $(this).addClass("is-active").siblings().removeClass('is-active');
   var jsExpanders = $('.data .teaser-data > .js-expander');
   for (var i = 0; i < jsExpanders.length; i++) {
     var jsExpander = jsExpanders.get(i);
     expander.open(jsExpander.id, true);
  }
});


$(function(){

    var checkExpandBox = function(){
      $('.js-expand-box').each(function(index) {
          var expanderBox = $(this);
          var openButton = expanderBox.find('~ .js-open-expand-text');
          var closeButton = expanderBox.find('~ .js-close-expand-text');
          var isActive = false;

          if(expanderBox.hasClass("is-active")){
            isActive = true;
          }

          closeButton.addClass('is-hidden');
          openButton.addClass('is-hidden');
          expanderBox.addClass('js-non-expand-text');
          expanderBox.removeClass('is-expand');

          var wrapHeight = expanderBox.find('.js-expand-text-content').height();
          var descHeight = expanderBox.height();

          if (wrapHeight <= descHeight) {
            expanderBox.find('.js-expand-text-fade').addClass('is-hidden');
          } else {
            expanderBox.removeClass('js-non-expand-text');
            if(isActive){
              expanderBox.addClass('is-expand');
              closeButton.removeClass('is-hidden');
            } else {
              openButton.removeClass('is-hidden');
            }
          }
      
          openButton.click(function() {
              $(this).addClass('is-hidden');
              expanderBox.addClass('is-active');
              expanderBox.addClass('is-expand');
              expanderBox.find('~ .js-close-expand-text').removeClass('is-hidden');
              expanderBox.find('.js-expand-text-fade').addClass('is-hidden');
          });
      
          closeButton.click(function() {
              $(this).addClass('is-hidden');
              expanderBox.removeClass('is-active');
              expanderBox.removeClass('is-expand');
              expanderBox.find('~ .js-open-expand-text').removeClass('is-hidden');
              expanderBox.find('.js-expand-text-fade').removeClass('is-hidden');
          });
          
        });
    };
    checkExpandBox();
    $(window).resize(function(){
        checkExpandBox();
    });
    var isAllOpen = true;
    var jsExpanders = $('.data .teaser-data .js-expander');
    if(jsExpanders.length > 0) {
        for (var i = 0; i < jsExpanders.length; i++) {
            var jsExpander = jsExpanders.get(i);
            if(!$(jsExpander).hasClass("is-hidden")){
                isAllOpen = false;
            }
        }
    } else {
      isAllOpen = false;
    }
    if(isAllOpen) {
      $('.js-toggle-all-expander-expand').addClass("is-active").siblings().removeClass('is-active');
    } else {
      $('.js-toggle-all-expander-collapse').addClass("is-active").siblings().removeClass('is-active');
    }
});
