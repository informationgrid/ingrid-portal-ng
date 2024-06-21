  // Add shrink class
  var body = $("body");
  var headerHeight = $('header').height();
  var bodyHeight = body.height();
  var headerBodyHeight = headerHeight + bodyHeight;
  var winOuterHeight = window.outerHeight;
  var isShrinkable = headerBodyHeight > ((winOuterHeight + 80) * 1.25);

  if(window.pageYOffset > headerHeight && isShrinkable) {
      body.addClass("shrink");
  }

  window.onscroll = function(e) {
    var filter = $('.filter');
    var scrollTop = $(this).scrollTop();
    winOuterHeight = window.outerHeight;
    isShrinkable = headerBodyHeight > ((winOuterHeight + 80) * 1.25);
    if (scrollTop > headerHeight && isShrinkable) {
      body.addClass("shrink");
      if(/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) {
        if(filter.length > 0) {
          var content = $('.search-filtered.row, .search-filtered > .row');
          var contentTop = 400;
          var contentMargin = 0;
          var diffTop = 25;
          if(content) {
            if(content.position()) {
              contentTop = content.position().top;
            }
            if(content.css("margin-top")) {
              contentMargin = parseInt(content.css("margin-top"));
            }

          }
          if(scrollTop > contentTop) {
            filter.css('top', scrollTop - contentTop + diffTop - contentMargin + 'px');
          }
          filter.css('position','relative');
        }
      }
    } else {
      body.removeClass("shrink");
      if(/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) {
        if(filter.length > 0) {
          filter.css('top','');
          filter.css('position','');
        }
      }
    }
  };