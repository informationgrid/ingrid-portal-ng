		window.addEvent('domready',function() {
			/* settings */
			var showDuration = 5000;
			var container = document.getElementById('slideshow-container');
			var images = container.getElementsByTagName('img');
			var currentIndex = 0;
			var interval;
			/* opacity and fade */
			for(var i=0; i<images.length; i++){
				var img = images.item(i);
				img.style.zIndex=100;
				if(i > 0) {
					img.style.zIndex=0;
					img.style.opacity = 0;
					img.style.filter='progid:DXImageTransform.Microsoft.Alpha(Opacity=0)';
				}
			}
			/* worker */
			var show = function() {
				images[currentIndex].style.opacity = 0;
				images[currentIndex].style.filter='progid:DXImageTransform.Microsoft.Alpha(Opacity=0)';
				images[currentIndex].style.zIndex=0;
				images[currentIndex = currentIndex < images.length - 1 ? currentIndex+1 : 0].style.opacity = 1;
				images[currentIndex].style.filter='progid:DXImageTransform.Microsoft.Alpha(Opacity=100)';
				images[currentIndex].style.zIndex=100;
				
			};
			/* start once the page is finished loading */
			window.addEvent('load',function(){
				interval = show.periodical(showDuration);
			});
		});