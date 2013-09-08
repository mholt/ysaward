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
	$('body .stay').first().find('header').length > 0
		? $('.stay').waypoint('sticky') 	// Desktop directory page, or complex pages, only
		: $('header').waypoint('sticky');	// Every other page

	// Make check boxes and radio buttons pretty
	$('input[type=radio], input[type=checkbox]').prettyCheckable();

	// BEGIN menu toggle
	var nv = $('nav');
	var nvWidthPosition = $(this).outerWidth() * -1;
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
	
	$('nav').mouseleave(function() {
		hideMenu();
	});

	$('body, a, button, input, label').click(function() {
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
	// END menu toggle

});