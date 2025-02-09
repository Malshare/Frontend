<?php

	$c_REFER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
	if($c_REFER != "") {
		# Handle Login requests
		$c_login = isset($_POST['api_key']) ? $_POST['api_key'] : "";
		if($c_login != "" ){
			include("server_includes.php");
			$tshare = new ServerObject();
			$uuser = $tshare->login();
			if ( $uuser->ready == True ) {
				setcookie('mapi_key', $tshare->uri_api_key, time() + (86400 * 30), "/");
			}
			unset($ushare);
			unset($uuser);
		}

		# Handle Logout calls
		if(isset($_POST['logout']) ){
			setcookie("mapi_key", "", time()-3600);
		}

		# Return back for all requests
		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}
	else {
		# Return to home if invalid request
		header("Location:index.php");
	}		

?>

