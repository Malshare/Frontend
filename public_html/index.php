<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>
	</head>
	<body>
        <?php include('nav.php') ?>

	<div class="container" style="width:90%">			
		<div class="container-fluid center text-center">
			<div class="row">

			<form method=get action=search.php id="search_form" class="form-search" onsubmit="ShowLoading()">
				<label class="lead" for="inputSearch">Quick Search: </label>
				<input type="text" name=query id='inputSearch' class="input-xxlarge">
				<button type="submit" class="btn">Search</button>
			</form>


			</div>
		</div>

		
		<p class="lead text-center">Recently added Samples</p>
			<?php
				include("server_includes.php");
				$share = new ServerObject();
				echo $share->get_recent();
			?>
	</div> 

		
	<?php include_once('footer.php'); ?>

	</body>
</html>

