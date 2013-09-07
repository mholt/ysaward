<meta charset="UTF-8">

<!-- Style sheets and fonts -->
<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Oswald|Open+Sans:400,300,700,800">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css">
<link rel="stylesheet" href="/resources/css/unsemantic.css">
<link rel="stylesheet" href="/resources/css/sticky.css">
<link rel="stylesheet" href="/resources/css/prettyCheckable.css">
<link rel="stylesheet" href="/resources/css/style.css">

<!-- Javascript -->
<script src="/resources/js/jquery2_0_3.min.js"></script>
<script src="/resources/js/jquery.hijax.min.js"></script>
<script src="/resources/js/jquery.sticky.min.js"></script>
<script src="/resources/js/jquery.form.min.js"></script>
<script src="/resources/js/jquery.prettycheckable.js"></script>
<script src="/resources/js/jquery.unveil.min.js"></script>
<script src="/resources/js/jquery.waypoints.min.js"></script>
<script src="/resources/js/script.js"></script>

<!-- Fill width -->
<meta name="viewport" content="width=device-width">

<!-- Disable pinch zooming -->
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">

<!-- Icons -->
<!--<link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="/favicon.ico" type="image/vnd.microsoft.icon">-->
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="apple-mobile-web-app-title" content="<?php echo SHORT_SITE_NAME; ?>">

<script>
/*
var img = new Image();
img.src = "<?php echo SITE_SMALL_IMG_HOVER; ?>";	// Pre-loads the hover image for the logo in the corner
$(function() {
	// Logo change color on hover
	$('#logo').hover(function() {
		$(this).attr('src', img.src);
	}, function() {
		$(this).attr('src',  "<?php echo SITE_SMALL_IMG; ?>");
	});
});*/

// GOOGLE ANALYTICS TRACKING CODE
var _gaq = _gaq || [];
if (location.hostname.indexOf(".dev") == -1)
{
	_gaq.push(['_setAccount', '<?php echo ANALYTICS_TRACKING_ID; ?>']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
}
</script>