<?php
	if($_SERVER['HTTP_REFERER'] != "") {

		# Handle Login requests
		if($_POST["api_key"] != "" ){
			include("server_includes.php");
			$tshare = new ServerObject();
			$uuser = new UserObject($tshare->sql, $tshare->uri_api_key, True);
			if ( $uuser->ready == True ) {
				setcookie('mapi_key', $tshare->uri_api_key, time() + (86400 * 30), "/");
			}
			unset($ushare);
			unset($uuser);
		}

		# Handle Logout calls
		if($_POST["logout"] != "" ){
			setcookie("mapi_key", "", time()-3600);
		}

		# Return back for all requests
		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}
	else {
		# Return to home if rando requests
		header("Location:index.php");
	}		

?>

