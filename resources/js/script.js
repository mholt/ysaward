(function($)
{
	// Stores button contents while its spinner is being displayed in its place
	var buttonHtmls = {};

	// HTML that makes up the spinner image while the forms submit
	var spinnerImg = '<img src="/resources/images/ajax-gray.gif">';


	$.fn.showSpinner = function()
	{
		buttonHtmls[this] = this.html();
		this.width(this.width());
		this.prop('disabled', true).html(spinnerImg);
		return this;
	};

	$.fn.hideSpinner = function()
	{
		this.css('width', '');
		this.prop('disabled', false).html(buttonHtmls[this]);
		delete buttonHtmls[this];
		return this;
	};
}(jQuery));

(function($)
{
	$.fn.notIn = function(sel)
	{
		return this.filter(function() {
			return $(this).parents(sel).length == 0;
		});
	}
}(jQuery));


$(function()
{
	// Load images when scrolled into view
	$('img').unveil();

	// Smooth-scroll to top when "to top" links are clicked
	$('a[href=#]').click(function(event) {
		$('html, body').stop().animate({ scrollTop: 0}, 700);
		return suppress(event);
	});

	// Keep any elements in the 'stay' class stuck to the top
	$('body .stay').first().find('header').length > 0
		? $('.stay').waypoint('sticky') 	// Desktop directory page, or complex pages, only
		: $('header').waypoint('sticky');	// Every other page

	// Make check boxes and radio buttons pretty
	$('input[type=radio], input[type=checkbox]').not('.standard').prettyCheckable();

	// BEGIN nav menu drawer toggle
	var nv = $('nav');
	var nvWidthPosition = nv.outerWidth() * -1;
	var menuVisible = false;
	$('#menu-icon').click(function() {
		if (!menuVisible)
			showMenu();
		else
			hideMenu();
	});

	$('#menu-icon').hover(function() {
		if (!menuVisible)
			showMenu();
	});
	
	$('nav').mouseleave(function() {
		hideMenu();
	});

	$('body *').not('nav').notIn('nav').click(function(e) {
		if (menuVisible)
			hideMenu();
	});

	function showMenu()
	{
		if (menuVisible)
			return;

		$('body').css('overflow', 'hidden');

		$('nav').animate({
			right: 0
		}, 150, 'swing', function() {
			menuVisible = true;
		});
	}

	function hideMenu()
	{
		if (!menuVisible)
			return;

		$('body').css('overflow', '');

		$('nav').animate({
			right: nvWidthPosition
		}, 150, 'swing', function() {
			menuVisible = false;
		});
	}
	// END nav menu drawer toggle
});


function suppress(event) {
	if (!event)
		return false;
	if (event.preventDefault)
		event.preventDefault();
	if (event.stopPropagation)
		event.stopPropagation();
	if (event.cancelBubble)
		event.cancelBubble = true;
	return false;
}


/*
A couple of snippets from the old version of the site:

// Determine whether this is a browser on a common mobile device
// and get some basic flags prepared for potential use later on.
var mobile = /Android|webOS|iPhone|iPad|Kindle|iPod|BlackBerry|Windows Phone/i.test(navigator.userAgent);
var isAndroid = /Android/i.test(navigator.userAgent);
var isChrome = /Chrome/i.test(navigator.userAgent);
var isIOS = /iPad|iPod|iPhone/i.test(navigator.userAgent);
var isSafari = /Safari/i.test(navigator.userAgent);
var isIE = /MSIE /i.test(navigator.userAgent);
var isFirefox = /Firefox/i.test(navigator.userAgent);
var isOpera = /Opera/i.test(navigator.userAgent);
var isWindows = /Windows/i.test(navigator.userAgent);
var isMac = /Macintosh/i.test(navigator.userAgent);
var isLinux = /Linux/i.test(navigator.userAgent);


// Courtesy of Artem Barger:
// http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript
function queryStringParam(name)
{
	name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
	var results = regex.exec(window.location.search);
	return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

*/