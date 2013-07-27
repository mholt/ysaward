<meta charset="UTF-8">
<link href="//fonts.googleapis.com/css?family=Arvo|PT+Sans+Narrow|Open+Sans:300,400,700" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="/resources/style.css">
<script type="text/javascript" src="/resources/lib.js"></script>
<script type="text/javascript" src="/resources/script.js"></script>
<link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="/favicon.ico" type="image/vnd.microsoft.icon">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="apple-mobile-web-app-title" content="<?php echo SHORT_SITE_NAME; ?>">
<!--[if lt IE 9]><script src="/resources/html5shiv.js" type="text/javascript"></script><![endif]-->
<script>
var img = new Image();
img.src = "<?php echo SITE_SMALL_IMG_HOVER; ?>";	// Pre-loads the hover image for the logo in the corner
$(function() {
	// Logo change color on hover
	$('#logo').hover(function() {
		$(this).attr('src', img.src);
	}, function() {
		$(this).attr('src',  "<?php echo SITE_SMALL_IMG; ?>");
	});
});

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