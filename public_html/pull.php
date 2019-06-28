<?php

if(array_key_exists("hash", $_POST) && $_POST["hash"]!="" ) {
        header("Location:sampleshare.php?action=getfile&api_key=".$_POST["api_key"]."&hash=".$_POST["hash"]);
        die();
}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
	<?php include('header.php'); ?>
	</head>

	<body>
        <?php include('nav.php') ?>

<div class="container" style="width:90%">
<div class="jumbotron">
<div class="container">		

			<form method=post action=pull.php class="form-signin">
				<h2 class="form-signin-heading">Pull Request</h2>
				<?php
				if (array_key_exists('mapi_key', $_COOKIE) && $_COOKIE['mapi_key'] != "" ){}
				else{
					echo '<input type="text" class="input-block-level" name=api_key placeholder="API Key">';
				}
				?>
				<input type="text" class="input-block-level" name=hash placeholder="MD5 / SHA1 / SHA256"> <br />

				
				<button class="btn btn-small btn-primary" type="submit">Submit</button>
			</form>
			
			
			 </div> 
			</div> 
	</div>

<?php
include_once('footer.php');
?>


  </body>
</html>

