<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
        	<?php include('header.php'); ?>
	</head>

	<body>
        <?php include('nav.php') ?>
	<div class="container">
		<div class="hero-unit">
		<center><img src="images/logo_header.png" width="333" height="119" alt="Malshare Logo" ></center>
		<p><?php echo h('thanks.intro'); ?></p>
		<br />
		<table class="table">
			<thead>
				<tr><th scope="col"><?php echo h('thanks.year'); ?></th><th scope="col"><?php echo h('thanks.name'); ?></th><th scope="col"><?php echo h('thanks.bug'); ?></th></tr>
			</thead>
			<tbody>
				<tr><td>2019</td><td>Aqib Shah</td><td>Registration XSS and ClickJacking</td></tr>
			</tbody>
			</table>




	
<?php
include_once('footer.php');
?>

  </body>
</html>
