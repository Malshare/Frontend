<!DOCTYPE html>
<html lang="en">
	<head>
	<meta charset="utf-8">
	<title>MalShare</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="The MalShare Project is a community driven public malware repository that works to provide free access to malware samples and tooling to the infomation security community.">

	
	
	<link href="./css/bootstrap.css" rel="stylesheet">
	<style type="text/css">
		body {
			padding-top: 40px;
			padding-bottom: 40px;
			background-color: #f5f5f5;
		}
      #push,
      #footer {
        height: 60px;
      }	
		.form-signin {
			max-width: 300px;
			padding: 19px 29px 29px;
			margin: 0 auto 20px;
			background-color: #fff;
			border: 1px solid #e5e5e5;
				-webkit-border-radius: 5px;
				-moz-border-radius: 5px;
			border-radius: 5px;
				-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
				-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
			box-shadow: 0 1px 2px rgba(0,0,0,.05);
		}
		.form-signin .form-signin-heading,
		.form-signin .checkbox {
			margin-bottom: 10px;
		}
		.form-signin input[type="text"],
		.form-signin input[type="password"] {
			font-size: 16px;
			height: auto;
			margin-bottom: 15px;
			padding: 7px 9px;
		}
		
	
	</style>
	<link href="./css/sticky-footer-navbar.css" rel="stylesheet">

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-49931431-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
	</head>

	<body>

<?php include('nav.php') ?>


<script type="text/javascript">
       // Source: https://stackoverflow.com/questions/39920900/disabling-submit-button-while-processing-on-server-without-jquery
    function ShowLoading(e) {
	setTimeout(function(){
	   window.location.reload(1);
	}, 20000);

        var div = document.createElement('div');
        var img = document.createElement('img');
	
        div.innerHTML = "<br /> <br /><h1>Pending Analysis...</h1><br />";

        img.src = 'images/ajax-loader.gif';
        div.style.cssText = 'position: fixed; top: 5%; left: 40%; z-index: 5000; width: 422px; text-align: center;';
        div.appendChild(img);
        document.body.appendChild(div);
        return true;
        // These 2 lines cancel form submission, so only use if needed.
        //window.event.cancelBubble = true;
        //e.stopPropagation();
    }
</script>
		<div class="container">			
			<div class="jumbotron">
<?php
	if($_GET["hash"]!="" && $_GET["action"]=="detail") {
		include("server_includes.php");
		$share = new ServerObject();
		echo $share->get_details();        
	}
	else{
		echo '<br /> <center><p class="lead">Please enter request with a hash</p></center>';
	
		die();
		
		
	}

?>

			
			
			</div>			
		</div> 
			
	

<?php
include_once('footer.php');
?>

			
  </body>
</html>

