<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>
	</head>
	<body>
        <?php include('nav.php') ?>

	<div class="container py-4">
		<div class="text-center mb-4">
			<div class="ms-search-bar">
				<form method="get" action="search.php" id="search_form">
					<label class="form-label fs-5 fw-semibold mb-2" for="inputSearch">Quick Search</label>
					<div class="input-group input-group-lg">
						<input type="text" name="query" id="inputSearch" class="form-control" placeholder="Search by hash, source, or filename...">
						<button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
					</div>
				</form>
			</div>
		</div>

		<script>
		document.getElementById('search_form').addEventListener('submit', function() {
			var overlay = document.createElement('div');
			overlay.className = 'ms-loading-overlay';
			overlay.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Searching…</span></div><div class="ms-loading-text">Searching…</div>';
			document.body.appendChild(overlay);
		});
		</script>

		<h5 class="ms-section-title">Recently Added Samples</h5>
			<?php
				include("server_includes.php");
				$share = new ServerObject();
				echo $share->get_recent();
			?>
	</div> 

		
	<?php include_once('footer.php'); ?>

	</body>
</html>
