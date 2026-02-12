<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>


	</head>

	<body>
        <?php
                include('nav.php')
        ?>
		<div class="container py-4">
			<div class="ms-form-card">
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
						$_SERVER["REMOTE_ADDR"],
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
                                                echo '<div class="text-center">
							<h3 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Code Problem</h3>
	                                                        <p>Invalid API Key</p>
		                                      </div>';
					} else {

						$result = $user->do_upgrade($code_post);
						
						if ($result[0] == true){
							echo '
							<div class="text-center">
								<h3 class="text-success"><i class="bi bi-check-circle me-2"></i>Code Successful</h3>
								<p>' . htmlspecialchars($result[1], ENT_QUOTES, 'UTF-8') . '</p>
							</div>
							';
						}
						else {
								echo '<div class="text-center">
								<h3 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Code Problem</h3>
								<p>' . htmlspecialchars($result[1], ENT_QUOTES, 'UTF-8') . '</p>
							</div>';
						}
					}					
				}
				else
				{
					echo '
					<form method="post" action="upgrade.php">
						<h2><i class="bi bi-arrow-up-circle me-2"></i>Upgrade Key</h2>
						<div class="mb-3">
							<input type="text" class="form-control" name="api_key" placeholder="API Key">
						</div>
						<div class="mb-3">
							<input type="text" class="form-control" name="code" placeholder="Upgrade Code">
						</div>
						<div class="text-center">
							<div class="d-inline-block mb-3"><div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div></div>
							<br />
							<button class="btn btn-primary" type="submit">Submit</button>
						</div>
					</form>
					';
				}
			?>
			</div>
		</div>
	
<?php
include_once('footer.php');
?>


  </body>
</html>

