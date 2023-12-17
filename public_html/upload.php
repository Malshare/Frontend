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
            header("Location:sample.php?action=detail&hash=" . $res['sha256']);
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

	<div class="container">
		<div class="jumbotron">
			<?php
				if ($errorMessage){
                    echo '<center><font color="red"><h4 class="form-signin-heading">' . $errorMessage. '</h2></font></center>';
				}
			?>

			<form method=post action=upload.php class="form-signin" enctype="multipart/form-data" >
				<h2 class="form-signin-heading">Upload</h2>
				<p><i>Uploaded files are publicly shared </i></p>
				<input type="file" name="fsample" id="fsample" class="fileupload" data-icon="false">
				<button class="btn btn-small btn-primary" onClick="return validate() && showScroll()" type="submit">Submit</button>
			</form>
		</div>
	</div>

	<?php include_once('footer.php'); ?>

  </body>
</html>

