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
				if (strlen($_POST["g-recaptcha-response"]) > 5) {
					$secret = getenv('MALSHARE_RECAPTCHA_SECRET');
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

				if ( array_key_exists( 'api_key', $_POST) &&  array_key_exists("code", $_POST) && 
					$_POST["api_key"]!="" && $_POST["code"]!="" && $capt_checked == true)  {

					include("server_includes.php");
				
					$share = new ServerObject();
					$user = new UserObject($share->sql, $share->uri_api_key, true);
					if ($user->active == 0){
                                                echo '<center><h3 class="form-signin-heading">Code Problem</h3>
	                                                        Invalid API Key <br />
		                                      </center>';
					} else {

						$result = $user->do_upgrade($_POST["code"]);
						
						if ($result[0] == true){
							echo '
							<center>
								<h3 class="form-signin-heading">Code Successful</h3>
								' . $result[1] . ' <br />
							</center>
							';
						}
						else {
				        	        echo '<center><h3 class="form-signin-heading">Code Problem</h3>
								' . $result[1] . ' <br />
							</center>';
						}
					}					
				}
				else
				{
					echo '
					<form method=post action=upgrade.php class="form-signin">
						<h2 class="form-signin-heading">Upgrade Key</h2>
						<input type="text" class="input-block-level" name=api_key placeholder="API Key"> <br />
						<input type="text" class="input-block-level" name=code placeholder="Upgrade Code">
						<center>
						      <div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div>
			    				<br />
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

