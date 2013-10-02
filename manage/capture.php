<?php
require_once("../lib/init.php");
protectPage(12);

$q = "SELECT ID, FirstName, LastName FROM Members WHERE WardID='{$WARD->ID()}' ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);


$typeahead = "[";
$ids = "{";
while ($mem = mysql_fetch_array($r))
{
	$namestr = '"'.$mem['FirstName']." ".$mem['LastName'].'"';
	
	if (strlen($typeahead) > 1)
		$typeahead .= ",";
	$typeahead .= $namestr;
	
	if (strlen($ids) > 1)
		$ids .= ",";
	$ids .= strtoupper($namestr).":".$mem['ID'];
}
$typeahead .= "]\n";
$ids .= "}";

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Test capture &mdash; <?php echo SITE_NAME; ?></title>
		<?php include("../includes/head.php"); ?>
		<script src="/resources/js/jquery.typeahead.min.js"></script>
		<style>
		.twitter-typeahead {
			display: block !important;
		}

		.twitter-typeahead p {
			margin: 0;
			font-size: 18px;
		}

		.tt-dropdown-menu {
			background: white;
			line-height: 1em;
			width: 100%;
		}

		.tt-suggestion {
			padding: 1em .5em;
			border-bottom: 1px solid #EEE;
		}

		.tt-is-under-cursor, .tt-suggestion:hover {
			background: #3C585B;
			color: #FFF;
		}

		#name {
			margin-bottom: 0;
		}

		#camera-input {
			visibility: hidden;
		}
		</style>
	</head>
	<body>
		<?php include "../includes/header.php"; ?>

		<h1>Take Pictures</h1>

		<form method="post" action="/manage/api/savepicture" enctype="multipart/form-data" class="narrow">

			<div class="text-center">
				<input type="text" name="name" id="name" placeholder="Type name">
				<input type="hidden" name="memberID" id="memid" value="">

				<input type="file" capture="camera" accept="image/jpeg" name="pic" id="camera-input">
				<button id="btn">Take Picture</button>
			</div>

			<div class="text-center">
				<br>
				<a href="/manage/capture">Reset</a><br>
			</div>

		</form>

		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>

<script>
$(function()
{
	// For relating member names to their ID
	var ids = <?php echo $ids; ?>;
	
	// Initialize the typeahead plugin
	$('#name').typeahead({
		name: 'member',
		local: <?php echo $typeahead; ?>
	}).on('typeahead:selected', function(e, data){
		$(this).change();
	});

	// When the name textbox changes, update the hidden member ID field
	$('#name').change(function() {
		$('#memid').val(ids[$(this).val().toUpperCase()]);
	});

	// Focus on page load
	$('#name').first().focus();

	// When the button is clicekd, submit the form only if a valid member is chosen
	$('#btn').click(function(event)
	{
		if (!$('#memid').val())
		{
			$('#name').focus();
			$.sticky("Please choose a member's name from the list as you type.", { classList: "error" } );
			return suppress(event);
		}

		// Take (or choose) a picture
		$('#camera-input').trigger('click');

		// Never let the form submit from here; we do this below manually
		return suppress(event);
	});

	// If a picture was chosen, submit the form
	$('#camera-input').change(function()
	{
		if ($(this).val().length > 0)
			$('form').submit();
	});


	// Submits the form (uploads the picture)
	$('form').ajaxForm({
		beforeSend: function(formData, jqForm, options)
		{
			//var queryString = $.param(formData); // Not needed, just an example. -- actually, this and the arguments go for "beforeSubmit" callback
			//status.empty();
			//var percentVal = '0%';
			//bar.width(percentVal)
			//percent.html(percentVal);
			if ($('#camera-input').val().length == 0)
				return false;

			$('#btn').showSpinner();
		},
		uploadProgress: function(event, position, total, percentComplete)
		{
			// Also not needed, but here in case we implement it
			//var percentVal = percentComplete + '%';
			//bar.width(percentVal)
			//percent.html(percentVal);
		},
		complete: function(jqxhr)
		{
			$('#camera-input').val('');

			if (jqxhr.status == 200)
			{
				$.sticky("Saved picture for " + $('#name').val() + "");
				$('#memid, #name, #camera-input').val('');
				$('#name').typeahead('setQuery', '').focus().trigger('click');;
			}
			else
			{
				$('#camera-input').val('');
				$.sticky(jqxhr.responseText || "There was a problem. Please check your Internet connection and try again.", { classList: "error" });
			}

			$('#btn').hideSpinner();
		}
	});
});
</script>
	</body>
</html>