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

// Selects elements not within another element (as a child)
$.expr[':'].notin = function(a, i, m)
{
	return $(a).parents(m[3]).length == 0;
};


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

	// BEGIN menu toggle
	var nv = $('nav');
	var nvWidthPosition = nv.outerWidth() * -1;
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

	$('body').on('click', ':not(nav):notin(nav)', function() {
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

	// Resize textareas vertically as it gets filled out
	/*$('textarea').keyup(function()
	{
		$(this).css('height', 'auto').css('height', this.scrollHeight+'px');
	});*/

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