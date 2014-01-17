<meta charset="UTF-8">

<!-- Style sheets and fonts -->
<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Oswald|Open+Sans:300,400,600,800">
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

<!-- Set viewport for mobile devices -->
<!--
	Be careful if setting initial-scale=1.0 and user-scalable=0 ...
	though ideal for iPads and most phones, it makes the page too
	wide on iPhones...
-->
<meta name="viewport" content="width=device-width, maximum-scale=1.0">

<!-- Android makes this look goooood. -->
<meta name="mobile-web-app-capable" content="yes">
<link rel="shortcut icon" sizes="196x196" href="favicon.png">

<!-- Icons -->
<!--<link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon">
<link rel="icon" href="/favicon.ico" type="image/vnd.microsoft.icon">-->


<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="apple-mobile-web-app-title" content="<?php echo SHORT_SITE_NAME; ?>">

<script>
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