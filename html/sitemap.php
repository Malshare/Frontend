<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
        <?php include('header.php'); ?>
	</head>

	<body>
        <?php include('nav.php'); ?>
	<div class="container" style="width:90%">			
      		<div class="hero-unit"> 
      			<div class="row">
        			<div class="span12">
						<p><?php echo h('sitemap.intro'); ?></p>
        			</div>
     			</div>
     		</div>
		<p class="lead"><?php echo h('sitemap.title'); ?></p>
		<p class="h4"><?php echo h('sitemap.recent_samples'); ?></p>
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
