<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>
	</head>

	<body>
        <?php include('nav.php'); ?>
	<div class="container" style="width:90%">			
      		<div class="hero-unit"> 
      			<div class="row">
        			<div class="span12">
                        <p>A free Malware repository providing researchers access to samples, malicous feeds, and Yara results.</p>
        			</div>
     			</div>
     		</div>
		<p class="lead">Sitemap</p>
		<p class="h4">Recent Samples</p>
		<p class="hash_font">	
			<?php
				include("server_includes.php");
				$share = new ServerObject();
				echo $share->get_sitemap();        
			?>
		</p>
		</div> 
		
	<?php include_once('footer.php'); ?>

  </body>
</html>

