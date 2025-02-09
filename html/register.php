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
		<div class="container">
			<br /> <br />
				<?php

				require_once "recaptchalib.php";
				$capt_checked = false;
				$secret = getenv('MALSHARE_RECAPTCHA_SECRET');
				if ($secret == "DISABLED") {
					$capt_checked = true;
				} else{
					if (strlen($_POST["g-recaptcha-response"]) > 5) {
						$response = null;
						$reCaptcha = new ReCaptcha($secret);
		
						$response = $reCaptcha->verifyResponse(
							$_SERVER["REMOTE_ADDR"],
							$_POST["g-recaptcha-response"]
						);
						if  ($response != null && $response->success) { 
							$capt_checked = true;
						}
					}
				}

				if ( array_key_exists( 'name', $_POST) &&  array_key_exists("email", $_POST) && 
					$_POST["name"]!="" && $_POST["email"]!="" && $capt_checked == true)  {

					include("server_registration.php");
				
					$h_register = new ServerObject();
					$result = $h_register->register();
					
					if ($result){
						echo '
						<center>
							<h3 class="form-signin-heading">Registration Successful.</h3>
							An API Key has been emailed to ' . $_POST["email"] . ' <br />
						</center>
						';
					}
					else {
			        	        echo '<h3 class="form-signin-heading">
							<center>Registration Problem</center></h3>
							<p> Email was either already registered or there was an error.  If registered, your API key will be emailed to ' . $_POST["email"] . ' (please check SPAM folder).  If you cannot find your registration, please contact an admin: Error 2587 - admin@malshare.com.</p>'; 
					}
					
				}
				else
				{
					echo '
					<form method=post action=register.php class="form-signin">
						<h2 class="form-signin-heading">Register</h2>
						<input type="text" class="input-block-level" name=name placeholder="Name"> <br />
						<input type="text" class="input-block-level" name=email placeholder="Email Address"><center>';
					if ($secret != "DISABLED"){
						echo '<div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div>';
			    }
			    echo '<br />
							<button class="btn btn-small btn-primary" type="submit">Submit</button>
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

