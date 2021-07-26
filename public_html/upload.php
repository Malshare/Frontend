<?php
$SUCCESS = false;
$RESULTS = "";
if( (array_key_exists( 'fsample', $_FILES ) && ($_FILES['fsample'])) ){
	if ($_FILES["fsample"]["size"] > 10000000) {
		$RESULTS = "File too Large: <i> 10Mb Max</i>";
	}
	else{
		include("server_includes.php");

		$h_server = new ServerObject();
		$sub_result = $h_server->upload_sample($_FILES['fsample']);
		if ( $sub_result == false ){
			if( $_REQUEST['mode'] == "cli"){
				die("Probem with Upload");
			}
			else {
				$RESULTS = "Possible Problem";
			}
		} else {
			if ($_REQUEST['mode'] == "cli" ){
				die("Uploaded");
			}
			else{
				$SUCCESS=true;
				header("Location:sample.php?action=detail&hash=" . $sub_result );
			}
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
			var size=10000000;
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
			<div class="alert alert-warning" role="alert">
				Sample processing is currently disabled.  Samples can still be uploaded, however, results will be delayed.
			</div>
			<?php
				if ($SUCCESS == false){
					if (strlen($RESULTS) > 1){
						echo '<center><font color="red"><h4 class="form-signin-heading">' . $RESULTS. '</h2></font></center>';
					}
				}
				else{
					echo '<center><font color="green"><h4 class="form-signin-heading">Thank you for your submission.  This file will be processed in the next few minutes</h2></font></center>';
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

