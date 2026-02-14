<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
        <?php include('header.php'); ?>
	</head>
	<body>
        <?php include('nav.php') ?>

	<div class="container" style="width:90%">			
		<div class="container-fluid center text-center">
			<div class="row">

			<form method=get action=search.php id="search_form" class="form-search" onsubmit="ShowLoading()">
				<label class="lead" for="inputSearch"><?php echo h('index.quick_search'); ?> </label>
				<input type="text" name=query id='inputSearch' class="input-xxlarge">
				<button type="submit" class="btn"><?php echo h('index.search'); ?></button>
			</form>


			</div>
		</div>

		
		<p class="lead text-center"><?php echo h('index.recent_samples'); ?></p>
			<?php
				include("server_includes.php");
				$share = new ServerObject();
				echo $share->get_recent();
			?>
	</div> 

		
	<?php include_once('footer.php'); ?>

	</body>
</html>
