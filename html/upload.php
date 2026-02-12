<?php
$errorMessage = '';
if ((array_key_exists('fsample', $_FILES) && ($_FILES['fsample']))) {
    if ($_FILES["fsample"]["size"] > 26214400) {
        $errorMessage = "File too Large: <i> 25MB Max</i>";
    } else {
        include("server_includes.php");

        $res = (new ServerObject())->upload_sample($_FILES['fsample']);
		if ($res['type'] === 'error') {
			$errorMessage = $res['message'];
		} elseif ($res['type'] === 'success') {
			header("Location:sample.php?action=detail&hash=" . rawurlencode($res['sha256']));
			exit();
		}
    }
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>
	</head>
	<body>

	<script>
		function validate(){
			var size=26214400;
			var file_size=document.getElementById('fsample').files[0].size;
			if(file_size>=size){
				alert('File too large');
				return false;
			}
		}
	</script>

	<?php include('nav.php'); ?>

	<div class="container py-4">
		<div class="ms-form-card">
			<?php
				if ($errorMessage){
					echo '<div class="alert alert-danger text-center" role="alert">' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</div>';
				}
			?>

			<form method="post" action="upload.php" enctype="multipart/form-data">
				<h2><i class="bi bi-cloud-arrow-up me-2"></i>Upload</h2>
				<p class="text-body-secondary"><em>Uploaded files are publicly shared</em></p>
				<div class="mb-3">
					<input type="file" name="fsample" id="fsample" class="form-control">
				</div>
				<button class="btn btn-primary" onClick="return validate()" type="submit">Submit</button>
			</form>
		</div>
	</div>

	<?php include_once('footer.php'); ?>

  </body>
</html>

