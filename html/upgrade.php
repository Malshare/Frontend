<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
        <?php include('header.php'); ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>


	</head>

	<body>
        <?php
                include('nav.php')
        ?>
		<div class="container">
			<br /> <br />
				<?php
				require_once "recaptchalib.php";
				$capt_checked = false;
				$g_recaptcha = filter_input(INPUT_POST, 'g-recaptcha-response') ?: '';
				$api_key_post = filter_input(INPUT_POST, 'api_key') ?: '';
				$code_post = filter_input(INPUT_POST, 'code') ?: '';

				if (strlen($g_recaptcha) > 5) {
					$secret = getenv('MALSHARE_RECAPTCHA_SECRET');
					$response = null;
					$reCaptcha = new ReCaptcha($secret);

					$response = $reCaptcha->verifyResponse(
						ServerObject::client_ip(),
						$g_recaptcha
					);
					if  ($response != null && $response->success) {
						$capt_checked = true;
					}
				}

				if ($api_key_post !== '' && $code_post !== '' && $capt_checked == true)  {

					include("server_includes.php");
				
					$share = new ServerObject();
					$user = new UserObject($share->sql, $api_key_post, true);
					if ($user->active == 0) {
												echo '<center><h3 class="form-signin-heading">' . h('upgrade.code_problem') . '</h3>
															' . h('upgrade.invalid_key') . ' <br />
												      </center>';
					} else {

						$result = $user->do_upgrade($code_post);
						
						if ($result[0] == true){
								echo '
								<center>
									<h3 class="form-signin-heading">' . h('upgrade.code_success') . '</h3>
									' . htmlspecialchars($result[1], ENT_QUOTES, 'UTF-8') . ' <br />
								</center>
								';
						}
						else {
								echo '<center><h3 class="form-signin-heading">' . h('upgrade.code_problem') . '</h3>
								' . htmlspecialchars($result[1], ENT_QUOTES, 'UTF-8') . ' <br />
						</center>';
						}
					}					
				}
				else
				{
					echo '
					<form method=post action=upgrade.php class="form-signin">
						<h2 class="form-signin-heading">' . h('upgrade.title') . '</h2>
						<input type="text" class="input-block-level" name=api_key placeholder="' . h('upgrade.api_key') . '"> <br />
						<input type="text" class="input-block-level" name=code placeholder="' . h('upgrade.code') . '">
						<center>
						      <div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div>
			    				<br />
							<button class="btn btn-small btn-primary" type="submit">' . h('upgrade.submit') . '</button>
						</center>
					</form>
					';
				}
			?>
		</div> 

      <div id="push"></div>
	
<?php
include_once('footer.php');
?>


  </body>
</html>
