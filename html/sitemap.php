<!DOCTYPE html>
<html lang="en">
	<head>
		<?php include('header.php'); ?>
	</head>

	<body>
	<?php include('nav.php'); ?>

	<div class="container py-4">
		<div class="ms-hero">
			<p class="mb-0">A free malware repository providing researchers access to samples and malicious feeds.</p>
		</div>

		<h5 class="ms-section-title">Sitemap</h5>

		<div class="ms-card">
			<h6 class="fw-semibold mb-3">Recent Samples</h6>
			<p class="hash_font">
				<?php
					include("server_includes.php");
					$share = new ServerObject();
					echo $share->get_sitemap();
				?>
			</p>
		</div>
	</div>

	<?php include_once('footer.php'); ?>

  </body>
</html>
