<?php

/* ****************************************** */
/* Norman SampleShare Server Framework        */
/* Version 1.30                               */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* ****************************************** */
/* Modified by Silas Cutler for Malshare.com  */
/* Version 1.0                                */
/*                                            */
/* ****************************************** */

error_reporting(E_ALL & ~E_NOTICE);



/* GLOBAL CONFIG VARS */

define(USERS_TABLE, "tbl_users");
define(DB_HOST, getenv('MALSHARE_DB_HOST'));
define(DB_USER, getenv('MALSHARE_DB_USER'));
define(DB_PASS, getenv('MALSHARE_DB_PASS'));
define(DB_DATABASE, getenv('MALSHARE_DB_DATABASE'));


class ServerObject {
	public static $sql;
	
	public $uri_action;

	public $vars_table_users;

	
	public $table;
	
	function __construct() {
		$this->host_ip = $_SERVER['REMOTE_ADDR'];
		
		$this->sql = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_DATABASE);	
		if(mysqli_connect_errno()) {
			die("ERROR! => 2809.  Please report to admin@malshare.com\n");
		}		
	
		$this->email = filter_var(strip_tags($this->secure($_REQUEST["email"])),FILTER_SANITIZE_EMAIL);
		$this->name = filter_var(strip_tags($this->secure($_REQUEST["name"])),FILTER_SANITIZE_STRING);
		$this->api_key = $this->generate_api_key();
		$this->valid = true;
		if(! preg_match("/^[A-Za-z0-9\.\-\_\+]*@[A-Za-z0-9\.\-\_]+$/",$_REQUEST["email"])){
			echo ('
			<center>
				Invalid Email Supplied 
			</center>	
			');
			$this->valid = false;
			return false;
		}


		$this->vars_table_users = USERS_TABLE;
	}

	
	public function secure($string) { 
		if(!$this->sql) die("Error");
			$string = strip_tags($string);
		if(get_magic_quotes_gpc()) {
			$string = stripslashes($string);
		}
	
		$string = $this->sql->real_escape_string($string);	
		return $string;
	}	

	public function register() { 
		if (! $this->valid) return false;

                $res = $this->sql->query("SELECT `name`, `email`, `api_key` from `tbl_users` WHERE `email` = '" . $this->email ."'");
                if(!$res) die("Error: 2191011.  Please contact admin@malshare.com");
		if ( mysqli_num_rows($res) == 1 ){
			$s_row = $res->fetch_object();
			$this->api_key = $s_row->api_key;
			$this->email= $s_row->email;
			$this->send_register_email();
			return false;
		}
		else {
			$reg_query = "INSERT INTO `tbl_users`(`name`, `email`, `api_key`, `approved`, `active`, `r_ip_address`) VALUES ('" . $this->name . "', '" . $this->email . "', '" . $this->api_key . "', 1 , 1, '" . $this->host_ip . "')";
		
			if (!$this->sql->query($reg_query)) {
				return false;
			}   
			else{
				return $this->send_register_email();
			}
		}
	}	


	function send_register_email(){
		require_once "Mail.php";
		
		$to = $this->email;
		$from = "Malshare Registration <registration@malshare.com>";
		$subject = "Malshare API Key";
		$body = '
Thank you for your interest in the MalShare research project. Below, you\'ll find your registrant name, email, and API key. 

Name    : ' . $this->name . '
Email   : ' . $this->email . '
API Key : ' . $this->api_key . ' 


Your free API key will allow you to pull 2000 samples per day. If you require more or have additional feature requests, please contact Admin@MalShare.com.

If you would like to show your support for the MalShare Project, please consider donating via paypal. 

Donate    : www.malshare.com/donate.php 
Resources : https://github.com/malshare 

The MalShare Project Team
www.malshare.com
';
		
		$host = getenv('MALSHARE_MAILGUN_SMTP'); 
		$port = intval(getenv('MALSHARE_MAILGUN_PORT'));
		$from = getenv('MALSHARE_MAILGUN_FROM'); 
		$username = getenv('MALSHARE_MAILGUN_USERNAME');
		$password = getenv('MALSHARE_MAILGUN_PASSWORD');
		
		$headers = array ('From' => $from,
		'To' => $to,
		'Subject' => $subject);
		$smtp = Mail::factory('smtp', array (
			'host' => $host,
			'port' => $port,
			'auth' => true,
			'username' => $username,
			'password' => $password)
		);
		
		$mail = $smtp->send($to, $headers, $body);
		
		if (PEAR::isError($mail)) {
			echo "Problem sending activation email.  Please contact admin@malshare.com";
			return false;
			
		} else {
			return true;
		}
			
	}


	function generate_api_key()
	{
		$pre_rand = base64_encode(openssl_random_pseudo_bytes(32));
		return hash('sha256', $pre_rand);
	}


}








?>
