(function($)
{
	// Stores button contents while its spinner is being displayed in its place
	var buttonHtmls = {};

	// HTML that makes up the spinner image
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


$(function()
{
	// Load images when scrolled into view
	$('img').unveil();

	// Keep any elements in the 'stay' class stuck to the top
	$('#content > .stay').first().find('header').length > 0
		? $('.stay').waypoint('sticky') 	// Desktop directory page, or complex pages, only
		: $('header').waypoint('sticky');	// Every other page

	// Make check boxes and radio buttons pretty
	$('input[type=radio], input[type=checkbox]').prettyCheckable();

	// BEGIN menu toggle
	var nv = $('nav');
	var menuVisible = false;
	$('#menu-icon').mousedown(function() {
		if (!menuVisible)
			showMenu();
		else
			hideMenu();
	});

	$('#menu-icon').hover(function() {
		if (!menuVisible)
			showMenu();
	});
	
	$('#content').mouseenter(function() {
		hideMenu();
	});

	$('#content, a, button, input, label').click(function() {
		if (menuVisible)
			hideMenu();
	});

	function showMenu()
	{
		if (menuVisible)
			return;

		nv.css('visibility', 'visible');
		$('#content, .stuck').stop().css('overflow', 'hidden').animate({
			left: $('nav').outerWidth() * -1
		}, 100, 'swing', function() {
			menuVisible = true;
		});
	}

	function hideMenu()
	{
		if (!menuVisible)
			return;

		$('#content, .stuck').stop().animate({
			left: 0
		}, 100, 'swing', function() {
			// The overflow fix here, and used above when showing the menu,
			// fixes problems with the table header row appearing over nav
			// when it's not fully scrolled to the right
			$(this).css('overflow', '');
			nv.css('visibility', 'hidden');
			menuVisible = false;
		});
	}
	// END menu toggle

});