<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>
	</head>

<body>
<?php include('nav.php') ?>

<script type="text/javascript">
    function ShowLoading(e) {
        var div = document.createElement('div');
        var img = document.createElement('img');
        img.src = 'images/ajax-loader.gif';
        div.style.cssText = 'position: fixed; top: 5%; left: 40%; z-index: 5000; width: 422px; text-align: center;';
        div.appendChild(img);
        document.body.appendChild(div);
        return true;
    }
</script>

<div class="container" style="width:90%">			
<div class="jumbotron">
			<?php
				include("server_includes.php");
				if( ($_POST["query"]!="") or ($_GET["query"] != "") ){
					$share = new ServerObject();

					$sample = $share->sample_search();
					echo $sample;


					$showDivFlag=false;
				} else{
					$showDivFlag=true;
				} ?>
<div class="container"  <?php if ($showDivFlag===false){?>style="display:none"<?php } ?>>

                        	<form method=get action=search.php class="form-signin" id="search_form" onsubmit="ShowLoading()">
                                	<h2 class="form-signin-heading">Search</h2>

					<div>
                                	<input type="text" class="input-block-level" name=query placeholder="Search hashes, sources and file names...">
					<label class="checkbox"><input type="checkbox" name=private > Private Search</label>
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
                echo '                  <tr><td>' . $skey . ' </td>';
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
</script>


	
<?php
include_once('footer.php');
?>

  </body>
</html>

