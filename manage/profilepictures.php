<?php
require_once("../lib/init.php");
protectPage(12);

// Get a list of all current members
$q = "SELECT ID FROM Members WHERE WardID=$MEMBER->WardID ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);


?>
<html>
<head>
	<title>Profile Pictures &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("../includes/head.php"); ?>
<style>
.loader {
	visibility: hidden;
	vertical-align: bottom;
}
</style>
</head>
<body>
	
	<?php include("../includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-10 prefix-1 suffix-1">
			
			<h1>Profile Pictures</h1>


			<div class="instructions">
				<p>
					Here you can set profile pictures for ward members. Unless your ward directs
					otherwise, please keep members' profile pictures if they chose their own.
					It is courteous to use a member's profile picture if they chose it themselves.
				</p>

				<p>
					<b>Only JPEG (or JPG) files are accepted</b> and each must be <b>under 2 MB</b> in size.
					This page is designed to help you upload pictures quickly. Once you select a file below,
					that upload will begin immediately. You are allowed to upload two pictures at a time.
					Uploads may be instantaneous or they may take several seconds to complete. Your ward
					shares server resources with others, so there's no performance guarantees.
				</p>
			</div>

			<br><br>
			
			<div class="text-center">
				<mark><b>Export profile pictures:</b> <a href="exportprofilepictures.php">Download <b>.zip</b> file</a></mark>
			</div>
			
			<br><br><br>
			

			<table class="wide" style="width: 100%;">
				<tr>
					<th>Current picture</th>
					<th class="text-left">Name</th>
					<th class="text-left">Options</th>
					<th class="text-left">Upload a picture</th>
				</tr>
		<?php
			$i = 0;

			// Display a row for each member
			while ($row = mysql_fetch_array($r))
			{
				$mem = Member::Load($row['ID']);
				if (!$mem)
					continue;

				// Display a thumbnail of any existing picture
				$curPic = $mem->PictureFile == null ? '<i>none</i>' : $mem->ProfilePicImgTag(true);
		?>
				<tr style="background: <?php echo $i % 2 == 0 ? '#EEE' : '#FFF'; ?>;">
					<td class="text-center"><?php echo $curPic; ?></td>
					<td><?php echo $mem->FirstName.' '.$mem->MiddleName.' '.$mem->LastName; ?></td>
					<td>
						<a href="api/deletepicture.php?member=<?php echo $mem->ID(); ?>" class="deletePicture">Delete</a>
					</td>
					<td>
						<form method="post" enctype="multipart/form-data" class="upl" action="api/savepicture.php">
							<input type="hidden" name="memberID" value="<?php echo $mem->ID(); ?>">
							<input type="file" accept="image/jpeg" name="pic" class="picture">
							<img src="/images/ajax-loader.gif" class="loader">
						</form>
					</td>
				</tr>
		<?php
			$i ++;
			}
		?>
			</table>

			<br><br>

			<div class="text-center"><a href="#">Back to top</a></div>


		</section>
		
	</article>
	
<?php include("../includes/footer.php"); ?>

<script type="text/javascript">

$(function() {

	// See editprofile.php for similar usage of this plugin, and links to documentation

	$('.picture').change(function (){
		$(this).closest('form.upl').submit();
	});

	var maxConcurrentUploads = 2; 	// How many uploads to allow at one time?
	var concurrentUploads = 0;		// Used to track how many uploads are currently processing


	$('form.upl').ajaxForm({
		beforeSubmit: function(formData, form, options)
		{
			if (concurrentUploads >= maxConcurrentUploads)
			{
				toastr.info("Only "+maxConcurrentUploads+" uploads allowed at a time. Please wait until another upload finishes.");
				return false;
			}

			concurrentUploads ++;

			$('.loader', form).css('visibility', 'visible');
			$('input:file', form).prop('disabled', true);
		},
		success: function(responseText, statusText, xhr, form)
		{
			if (xhr.status != 200)
				toastr.error("Something went wrong: "+xhr.responseText);

			// Member ID is passed into the response text
			if (xhr.status != 200)
				toastr.error("Something went wrong: "+xhr.responseText);
			else
			{
				toastr.success("Saved picture");
				replacePicture(form, xhr.responseText);
			}

			concurrentUploads --;
			$('.loader', form).css('visibility', 'hidden');
			$('input:file', form).val('').prop('disabled', false);
		}
	});


	$('.deletePicture').hijax({
		before: function()
		{
			if ($(this).data('processing'))
				return false;

			if (!confirm("Are you sure?"))
				return false;

			$(this).data('processing', true);
		},
		complete: function(xhr)
		{
			// Member ID is passed into the response text
			if (xhr.status == 200)
			{
				toastr.success("Deleted picture");
				replacePicture($(this), xhr.responseText);
			}
			else
				toastr.error(xhr.responseText);

			$(this).data('processing', false);
		}
	});

	function replacePicture(originElement, memID)
	{
		$.hijax({
			url: 'api/picturepath.php?member='+memID+'&thumbnail=true',
			complete: function(xhr) {
				originElement.closest('tr')
					.find('.profilePicture')
					.css('opacity', '0')
					.attr('src', xhr.responseText)
					.animate({ opacity: '1' }, 2000);
			}
		});
	}

});

</script>

</body>
</html>