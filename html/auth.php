<?php

	$c_REFER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
	if($c_REFER != "") {
		# Handle Login requests
		$c_login = filter_input(INPUT_POST, 'api_key') ?: '';
		if ($c_login !== '') {
			include("server_includes.php");
			$tshare = new ServerObject();
			// replace uri_api_key with provided login key for login attempt
			$uuser = new UserObject($tshare->sql, $c_login, true);
			if ($uuser->ready == True) {
				setcookie('mapi_key', $c_login, time() + (86400 * 30), "/");
				$login_ip = ServerObject::client_ip();
				$stmt = $tshare->sql->prepare('UPDATE tbl_users SET login_count = login_count + 1, last_login = UNIX_TIMESTAMP(), last_login_ip_address = ? WHERE id = ?');
				$stmt->bind_param('si', $login_ip, $uuser->id);
				$stmt->execute();
				$stmt->close();
			}
			unset($ushare);
			unset($uuser);
		}

		# Handle Logout calls
		if (filter_has_var(INPUT_POST, 'logout')) {
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

