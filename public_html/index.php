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
      		<div class="hero-unit"> 
      			<div class="row">
        			<div class="span12">
                        <p>A free Malware repository providing researchers access to samples, malicous feeds, and Yara results.</p>
        			</div>
     			</div>
     		</div>
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
			

				echo "<center><h4>Total Samples:" . $share->get_total() . "</h4></center>";

			?>
	</div> 

		
	<?php include_once('footer.php'); ?>

	</body>
</html>

