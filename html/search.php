<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js">  
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>

	</head>
<body>
<?php include('nav.php') ?>

<div class="container" style="width:90%">			
<div class="jumbotron">
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
				<div class="container" <?php if ($showDivFlag===false){?>style="display:none"<?php } ?>>

					<form method="get" action="search.php" class="form-signin" id="search_form" onsubmit="ShowLoading()">
                	<h2 class="form-signin-heading">Search</h2>

					<div>
						<input type="text" class="input-block-level" name="query" placeholder="Search hashes, sources and file names..." value="<?php echo $display_query; ?>">
						<label class="checkbox"><input type="checkbox" name="private" <?php echo $private_checked; ?> > Private Search</label>
                    	<button class="btn btn-small btn-primary" type="submit">Submit</button>
					<div class="popup" onclick="myFunction()"> Syntax
						<span class="popuptext" id="myPopup">
						Specific Search:<br />>  [md5 | sha1 | sha256 | source]: (query) <br />  Broad:<br />>    (query)
						</span>
					</div>
				</div>
				<br />
				<table class="table table-bordered table-striped">
				<thead> <tr>  <th><h4>Recent Searches</h4></th>  </tr> </thead>
				<tbody>

					<?php
						$share = new ServerObject();
						$stats = $share->get_recent_searches();
						foreach ($stats as $skey ){
							echo '                  <tr><td>' . $share->escape_html($skey) . ' </td>';
						}
					?>
					</tbody></table>

                        	</form>
                	</div>

			
			</div>			
		</div> 
<script>
function myFunction() {
    var popup = document.getElementById("myPopup");
    popup.classList.toggle("show");
}
function ShowLoading(e) {
        setTimeout(function(){
        window.location.reload(1);
     }, 20000);

     var div = document.createElement('div');
     var img = document.createElement('img');
       
     div.innerHTML = "<br /> <br /><h1>Searching...</h1><br />";

     img.src = 'images/ajax-loader.gif';
     div.style.cssText = 'position: fixed; top: 5%; left: 40%; z-index: 5000; width: 422px; text-align: center;';
     div.appendChild(img);
     document.body.appendChild(div);
     return true;
     // These 2 lines cancel form submission, so only use if needed.
     //window.event.cancelBubble = true;
     //e.stopPropagation();
}

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

