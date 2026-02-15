<?php
require_once __DIR__ . '/include/i18n.php';

$hash_post = filter_input(INPUT_POST, 'hash') ?: '';
$api_post = filter_input(INPUT_POST, 'api_key') ?: '';
if ($hash_post !== '') {
	$loc = 'sampleshare.php?action=getfile&api_key=' . rawurlencode($api_post) . '&hash=' . rawurlencode($hash_post);
	header('Location: ' . $loc);
	die();
}

?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
	<?php include('header.php'); ?>
	</head>

	<body>
        <?php include('nav.php') ?>

<div class="container" style="width:90%">
<div class="jumbotron">
<div class="container">		

			<form method=post action=pull.php class="form-signin">
				<h2 class="form-signin-heading"><?php echo h('pull.title'); ?></h2>
				<?php
				if (array_key_exists('mapi_key', $_COOKIE) && $_COOKIE['mapi_key'] != "" ){}
				else{
						echo '<input type="text" class="input-block-level" name=api_key placeholder="' . h('nav.api_key') . '">';
				}
				?>
					<input type="text" class="input-block-level" name=hash placeholder="<?php echo h('pull.hash_placeholder'); ?>"> <br />

				
					<button class="btn btn-small btn-primary" type="submit"><?php echo h('pull.submit'); ?></button>
			</form>
			
			
			 </div> 
			</div> 
	</div>

<?php
include_once('footer.php');
?>


  </body>
</html>
