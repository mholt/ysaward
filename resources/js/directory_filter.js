$(function()
{
	var males = $('.male'), females = $('.female');
	var maleCount = males.length, femaleCount = females.length;
	var allCount = $('#count').text();

	$('#show-guys').change(function()
	{
		$(this).is(':checked') ? males.show() : males.hide();
	});

	$('#show-girls').change(function()
	{
		$(this).is(':checked') ? females.show() : females.hide();
	});

	// Make sure all the visible pictures load; this makes sure that happens (with lazy loading)
	$('#show-guys, #show-girls').change(function()
	{
		var count = 0;
		if ($('#show-guys').is(':checked'))
			count += maleCount;
		if ($('#show-girls').is(':checked'))
			count += femaleCount;
		$('#count').text(count);
		$('img').trigger('unveil');
	});
});