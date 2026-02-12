<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

	</head>
<body>
<?php include('nav.php') ?>

<div class="container py-4">
			<?php
				include("server_includes.php");
                $post_query = filter_input(INPUT_POST, 'query') ?: '';
                $get_query = filter_input(INPUT_GET, 'query') ?: '';
				if (($post_query !== '') || ($get_query !== '')) {
					$share = new ServerObject();

					$sample = $share->sample_search();
					echo $sample;

					$showDivFlag=false;
				} else{
					$showDivFlag=true;
				}

				// Pre-fill search input with the sanitized query (prefer POST over GET)
				$display_query = htmlspecialchars(($post_query !== '' ? $post_query : $get_query), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
				// Preserve private checkbox state
				$private_flag = filter_input(INPUT_GET, 'private');
				$private_checked = ($private_flag !== null && $private_flag !== false && $private_flag !== '') ? 'checked' : '';
				?>
				<div <?php if ($showDivFlag===false){?>style="display:none"<?php } ?>>

					<div class="ms-form-card">
						<form method="get" action="search.php" id="search_form">
							<h2><i class="bi bi-search me-2"></i>Search</h2>

							<div class="mb-3">
								<input type="text" class="form-control" name="query" placeholder="Search hashes, sources and file names..." value="<?php echo $display_query; ?>">
							</div>
							<div class="form-check mb-3">
								<input class="form-check-input" type="checkbox" name="private" id="privateCheck" <?php echo $private_checked; ?>>
								<label class="form-check-label" for="privateCheck">Private Search</label>
							</div>
							<div class="d-flex align-items-center gap-2">
								<button class="btn btn-primary" type="submit">Submit</button>
								<div class="popup" onclick="myFunction()">
									<span class="badge bg-secondary" role="button"><i class="bi bi-info-circle me-1"></i>Syntax</span>
									<span class="popuptext" id="myPopup">
										Specific Search:<br />&gt; [md5 | sha1 | sha256 | source]: (query) <br />Broad:<br />&gt; (query)
									</span>
								</div>
							</div>
						</form>

						<hr class="my-4">

						<table class="table table-striped table-hover">
							<thead><tr><th><h6 class="mb-0">Recent Searches</h6></th></tr></thead>
							<tbody>
								<?php
									$share = new ServerObject();
									$stats = $share->get_recent_searches();
									foreach ($stats as $skey ){
										echo '<tr><td>' . $share->escape_html($skey) . '</td>';
									}
								?>
							</tbody>
						</table>
					</div>

				</div>

</div> 
<script>
function myFunction() {
    var popup = document.getElementById("myPopup");
    popup.classList.toggle("show");
}

document.getElementById('search_form').addEventListener('submit', function() {
    var overlay = document.createElement('div');
    overlay.className = 'ms-loading-overlay';
    overlay.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Searching…</span></div><div class="ms-loading-text">Searching…</div>';
    document.body.appendChild(overlay);
});

$(document).ready( function () {
	$('#searchres').DataTable({
	        "paging":   false,
		"searching" : false,
		"bInfo" : false,
    		"language": {
      			"emptyTable": "  "
    		}
	});
} );

</script>


	
<?php
include_once('footer.php');
?>

  </body>
</html>
