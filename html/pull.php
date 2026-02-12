<?php

$hash_post = filter_input(INPUT_POST, 'hash') ?: '';
$api_post = filter_input(INPUT_POST, 'api_key') ?: '';
if ($hash_post !== '') {
	$loc = 'sampleshare.php?action=getfile&api_key=' . rawurlencode($api_post) . '&hash=' . rawurlencode($hash_post);
	header('Location: ' . $loc);
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

<div class="container py-4">
	<div class="ms-form-card">

		<form method="post" action="pull.php">
			<h2><i class="bi bi-download me-2"></i>Pull Request</h2>
			<?php
			if (array_key_exists('mapi_key', $_COOKIE) && $_COOKIE['mapi_key'] != "" ){}
			else{
				echo '<div class="mb-3"><input type="text" class="form-control" name="api_key" placeholder="API Key"></div>';
			}
			?>
			<div class="mb-3">
				<input type="text" class="form-control" name="hash" placeholder="MD5 / SHA1 / SHA256">
			</div>

			<button class="btn btn-primary" type="submit">Submit</button>
		</form>

	</div>
</div>

<?php
include_once('footer.php');
?>


  </body>
</html>

