window.addEvent('domready',function() {
  /* settings */
  var showDuration = 3000;
  var container = $('slideshow-container');
  var images = container.getElements('img');
  var currentIndex = 0;
  var interval;
  /* opacity and fade */
  images.each(function(img,i){ 
    if(i > 0) {
      img.set('opacity',0);
    }
  });
  /* worker */
  var show = function() {
    images[currentIndex].fade('out');
    images[currentIndex = currentIndex < images.length - 1 ? currentIndex+1 : 0].fade('in');
  };
  /* start once the page is finished loading */
  window.addEvent('load',function(){
    interval = show.periodical(showDuration);
  });
});
