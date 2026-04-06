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
				include("server_includes.php");
				$capt_checked = false;
				$secret = getenv('MALSHARE_RECAPTCHA_SECRET');
				$g_recaptcha = filter_input(INPUT_POST, 'g-recaptcha-response') ?: '';
				$name_post = filter_input(INPUT_POST, 'name') ?: '';
				$email_post = filter_input(INPUT_POST, 'email') ?: '';

				if ($secret == "DISABLED") {
					$capt_checked = true;
				} else {
					if (strlen($g_recaptcha) > 5) {
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
				}

				if ($name_post !== '' && $email_post !== '' && $capt_checked == true)  {

					$share = new ServerObject();
					$result = $share->register_user($name_post, $email_post);

					if ($result['ok']) {
						// Both fresh registration and "already registered"
						// land here — the user gets the same friendly success
						// page either way, so re-registering does not produce
						// a confusing "problem" screen.
						echo '
						<center>
							<h3 class="form-signin-heading">' . h('register.success_title') . '</h3>
							' . h('register.success_body', array('email' => $result['email'])) . ' <br />
						</center>
						';
					} elseif ($result['error'] === 'invalid_email') {
						echo '
						<center>
							' . h('server.invalid_email') . ' : ' . htmlspecialchars($result['email'], ENT_QUOTES, 'UTF-8') . '
						</center>
						';
					} elseif ($result['error'] === 'disposable_email') {
						echo '
						<center>
							<b>' . h('server.temp_email_not_allowed') . ' : ' . htmlspecialchars($result['email'], ENT_QUOTES, 'UTF-8') . '</b>
						</center>
						';
					} else {
						// db_error
						echo '<h3 class="form-signin-heading">
							<center>' . h('register.problem_title') . '</center></h3>
							<p> ' . h('register.problem_body', array('email' => $email_post)) . '</p>';
					}

				}
				else
				{
					echo '
					<form method=post action=register.php class="form-signin">
						<h2 class="form-signin-heading">' . h('register.title') . '</h2>
						<input type="text" class="input-block-level" name=name placeholder="' . h('register.name') . '"> <br />
						<input type="text" class="input-block-level" name=email placeholder="' . h('register.email') . '"><center>';
					if ($secret != "DISABLED"){
						echo '<div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div>';
			    }
			    echo '<br />
							<button class="btn btn-small btn-primary" type="submit">' . h('register.submit') . '</button>
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
