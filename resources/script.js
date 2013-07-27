var speed = "fast";		// Preferred speed of any effects (slow, medium, fast, can also be # of ms)

// Determine whether this is a browser on a common mobile device
// and get some basic flags prepared for potential use later on.
var mobile = /Android|webOS|iPhone|iPad|Kindle|iPod|BlackBerry/i.test(navigator.userAgent);
var isAndroid = /Android/i.test(navigator.userAgent);
var isChrome = /Chrome/i.test(navigator.userAgent);
var isIOS = /iPad|iPod|iPhone/i.test(navigator.userAgent);
var isSafari = /Safari/i.test(navigator.userAgent);
var isIE;   // TODO
var isFirefox = /Firefox/i.test(navigator.userAgent);
var isOpera = /Opera/i.test(navigator.userAgent);
var isWindows = /Windows/i.test(navigator.userAgent);
var isMac = /Macintosh/i.test(navigator.userAgent);
var isLinux = /Linux/i.test(navigator.userAgent);


// Courtesy of Artem Barger: http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript
function queryStringParam(name)
{
	name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
	var results = regex.exec(window.location.search);
	return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}




$(function()
{
	// Smooth scroll to top of page
	$('a[href=#]').click(function(event) {
		$('html, body').stop().animate({ scrollTop: 0}, 1000);
		return suppress(event);
	});


	// jQuery lazy-load plugin (load images, specifically profile pics, when scrolled to)
	$("img.lazy").lazyload({ 
		effect : "fadeIn"
	});


	// Special thanks to Jeff Andersen for this mobile-friendly addition that handles the hover menus gracefully
	if (mobile)
	{
		$('nav > ul > li:has(.dropdown) > a').click(function(e) {
			e.preventDefault();
		});
		
		$('nav li:has(.dropdown)').click(function() {
			var toHide = $('.dropdown').not($(this).parents('li').andSelf().find('.dropdown'));
			var toShow = $(this).find('> .dropdown');
			
			toHide.hide();
			toShow.css('display', 'block');
				/*	more reliable than .show(), since the element may momentarily be visible via
					css, and .show() does nothing to visible objects	*/
		});
		
		$('body').click(function(e) {
			if ($(e.target).is(':not(nav *, nav)')) {
				$('nav .dropdown').hide();
			}
		});
	}

});