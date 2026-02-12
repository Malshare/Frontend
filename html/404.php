<?php
http_response_code(200);
header('X-Soft-404: 1');
header('X-Robots-Tag: noindex, follow');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include('header.php'); ?>
</head>
<body>
	<?php include('nav.php'); ?>

	<div class="container py-4">
		<div class="ms-card text-center">
			<h1 class="display-4 text-body-secondary mb-3"><i class="bi bi-exclamation-circle"></i></h1>
			<h2>Page Not Found</h2>
			<p class="text-body-secondary">Sorry — the page you requested could not be found. Try one of the options below or run a search.</p>

			<div class="d-flex justify-content-center gap-2 mb-4">
				<a href="index.php" class="btn btn-outline-primary">Homepage</a>
				<a href="search.php" class="btn btn-outline-primary">Search Samples</a>
				<a href="upload.php" class="btn btn-outline-primary">Upload</a>
			</div>

			<form action="search.php" method="get" class="d-flex justify-content-center gap-2" style="max-width: 500px; margin: 0 auto;">
				<input type="text" name="query" class="form-control" placeholder="Search by hash or term">
				<button type="submit" class="btn btn-primary">Search</button>
			</form>

			<hr class="my-4">
			<p class="text-body-secondary mb-0">If you believe this is an error, please <a href="mailto:admin@malshare.com">contact the site administrator</a>.</p>
		</div>
	</div>

	<?php include_once('footer.php'); ?>
</body>
</html>
