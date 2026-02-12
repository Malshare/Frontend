<!DOCTYPE html>
<html lang="en">
	<head>
		<?php include('header.php'); ?>
	</head>

	<body>
	<?php include('nav.php') ?>

	<div class="container py-4">
		<div class="ms-hero text-center mb-4">
			<img src="images/logo_header.png" width="333" height="119" alt="Malshare Logo" class="mb-3">
			<p class="lead mb-0">The MalShare Admin Team would like to recognize and extend thanks to the following individuals for their service in responsibly reporting vulnerabilities:</p>
		</div>

		<div class="ms-card">
			<table class="table table-striped table-hover mb-0">
				<thead>
					<tr><th scope="col">Year</th><th scope="col">Name/Handle</th><th scope="col">Bug</th></tr>
				</thead>
				<tbody>
					<tr><td>2019</td><td>Aqib Shah</td><td>Registration XSS and ClickJacking</td></tr>
				</tbody>
			</table>
		</div>
	</div>

<?php
include_once('footer.php');
?>

  </body>
</html>
