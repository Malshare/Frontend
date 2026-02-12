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
							$_SERVER["REMOTE_ADDR"],
							$g_recaptcha
						);
						if  ($response != null && $response->success) {
							$capt_checked = true;
						}
					}
				}

				if ($name_post !== '' && $email_post !== '' && $capt_checked == true)  {

					include("server_registration.php");
				
					$h_register = new ServerObject();
					$result = $h_register->register();
					
                    			if ($result){
                    			safe_echo_email:;
                        		echo '
                        		<div class="text-center">
                        			<h3 class="text-success"><i class="bi bi-check-circle me-2"></i>Registration Successful</h3>
                        			<p>An API Key has been emailed to ' . htmlspecialchars($email_post, ENT_QUOTES, 'UTF-8') . '</p>
                        		</div>
                        		';
                    		}
					else {
                        	        	echo '<div class="text-center">
						<h3 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Registration Problem</h3>
                            			<p>Email was either already registered or there was an error. If registered, your API key will be emailed to ' . htmlspecialchars($email_post, ENT_QUOTES, 'UTF-8') . ' (please check SPAM folder). If you cannot find your registration, please contact an admin: Error 2587 - admin@malshare.com.</p>
					</div>'; 
					}
					
				}
				else
				{
					echo '
					<form method="post" action="register.php">
						<h2><i class="bi bi-person-plus me-2"></i>Register</h2>
						<div class="mb-3">
							<input type="text" class="form-control" name="name" placeholder="Name">
						</div>
						<div class="mb-3">
							<input type="text" class="form-control" name="email" placeholder="Email Address">
						</div>
						<div class="text-center">';
					if ($secret != "DISABLED"){
						echo '<div class="d-inline-block mb-3"><div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div></div>';
			    }
			    echo '<br />
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

