<?php
require_once "../lib/init.php";
protectPage(12);

// Get a list of all current members
$q = "SELECT ID FROM Members WHERE WardID=$MEMBER->WardID ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);


?>
<!DOCTYPE html>
<html>
	<head>
		<title>Manage profile pictures &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "../includes/head.php"; ?>
		<style>
		.loader {
			visibility: hidden;
			vertical-align: bottom;
		}

		.del {
			font-size: 12px;
		}

		.profilePicture {
			max-width: <?php echo Member::THUMB_DIM / 2; ?>px;
			max-height: <?php echo Member::THUMB_DIM / 2; ?>px;
		}

		tr:nth-child(even) {
			background: #FFF;
		}

		tr:nth-child(odd) {
			background: #EFEFEF;
		}
		</style>
	</head>
	<body>
		<?php include "../includes/header.php"; ?>

		<h1>Profile pictures</h1>

		<div class="grid-container">

			<div class="grid-100">

				<div class="instructions">
					<p>
						Here you can set profile pictures for ward members. Unless your ward directs
						otherwise, please keep members' profile pictures if they chose their own.
						It is courteous to use a member's profile picture if they chose it themselves.
					</p>

					<p>
						<b>Only JPEG (or JPG) files are accepted</b> and each must be <b>under <?php echo ini_get('upload_max_filesize'); ?>B</b> in size.
						This page is designed to help you upload pictures quickly. Once you select a file below,
						that upload will begin immediately. You are allowed to upload two pictures at a time.
						Uploads may be instantaneous or they may take several seconds to complete. Your ward
						shares server resources with others, so there's no performance guarantees.
					</p>
				</div>

				<br>
				
				<div class="text-center">
					<mark><b>Export profile pictures:</b> <a href="exportprofilepictures.php">Download <b>.zip</b> file</a></mark>
				</div>
				
				<br><br>
				

				<table class="wide" style="width: 100%;">
					<tr>
						<th>Current picture</th>
						<th class="text-left">Name</th>
						<th class="text-left">Options</th>
						<th class="text-left">Upload a picture</th>
					</tr>
			<?php
				// Display a row for each member
				while ($row = mysql_fetch_array($r)):
					$mem = Member::Load($row['ID']);
					if (!$mem)
						continue;

					// Display a thumbnail of any existing picture
					$curPic = $mem->PictureFile == null
								? '<i>none</i><img class="profilePicture hide">'
								: '<img src="'.$mem->PictureFile(true).'" class="profilePicture">';
			?>
					<tr>
						<td class="text-center"><?php echo $curPic; ?></td>
						<td><?php echo $mem->FirstName.' '.$mem->MiddleName.' '.$mem->LastName; ?></td>
						<td>
							<a href="api/deletepicture.php?member=<?php echo $mem->ID(); ?>" class="deletePicture del"><i class="icon-remove-sign"></i> Delete</a>
						</td>
						<td>
							<form method="post" enctype="multipart/form-data" class="upl" action="api/savepicture">
								<input type="hidden" name="memberID" value="<?php echo $mem->ID(); ?>">
								<input type="file" accept="image/jpeg" name="pic" class="picture">
								<img src="/resources/images/pic-loader.gif" class="loader">
							</form>
						</td>
					</tr>
			<?php endwhile; ?>
				</table>

				<br><br>

				<div class="text-center">
					<a href="#"><i class="icon-arrow-up"></i> Top</a>
				</div>
			</div>

		</div>


		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>

<script>

$(function()
{

	// See /profile.php for similar usage of this plugin, and links to documentation

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
				$.sticky("Only "+maxConcurrentUploads+" uploads allowed at a time. Please wait until another upload finishes.", { classList: 'error' });
				return false;
			}

			concurrentUploads ++;

			$('.loader', form).css('visibility', 'visible');
			$('input:file', form).prop('disabled', true);
		},
		success: function(responseText, statusText, xhr, form)
		{
			if (xhr.status != 200)
				$.sticky("Something went wrong: "+xhr.responseText, { classList: 'error' });

			// Member ID is passed into the response text
			if (xhr.status != 200)
				$.sticky("Something went wrong: "+xhr.responseText, { classList: 'error' });
			else
			{
				$.sticky("Saved picture");
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
				$.sticky("Deleted picture");
				replacePicture($(this), xhr.responseText);
			}
			else
				$.sticky(xhr.responseText, { classList: 'error' });

			$(this).data('processing', false);
		}
	});

	function replacePicture(originElement, memID)
	{
		$.hijax({
			url: 'api/picturepath?member='+memID+'&thumbnail=true',
			complete: function(xhr) {
				originElement.closest('tr')
					.find('.profilePicture')
					.css('opacity', '0')
					.show()
					.attr('src', xhr.responseText)
					.animate({ opacity: '1' }, 2000);
			}
		});
	}
});

</script>
	</body>
</html>