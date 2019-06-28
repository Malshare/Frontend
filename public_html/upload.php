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
//			echo var_dump($_REQUEST);
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
	<meta charset="utf-8">
	<title>MalShare - Upload</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="The MalShare Project is a community driven public malware repository that works to provide free access to malware samples and tooling to the infomation security community.">

	<link href="./css/bootstrap.css" rel="stylesheet">
	<link href="./css/upload_page.css" rel="stylesheet">
	<link href="./css/sticky-footer-navbar.css" rel="stylesheet">

	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-49931431-1']);
		_gaq.push(['_trackPageview']);

		(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
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
			<br /> <br />
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

