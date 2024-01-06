<?php
	error_reporting(E_ALL & ~E_NOTICE);

	if(count(get_included_files()) ==1) {
		 header("Location:index.php");
	}
?> 

<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container-fluid">
			<a class="brand" href="/" name="top">&nbsp;&nbsp;&nbsp;&nbsp;<b>Mal</b>Share</a>
			<div class="nav-collapse collapse">
				<ul class="nav">

<li <?php if (stripos($_SERVER['REQUEST_URI'],'index.php') !== false) {echo 'class="active"';} ?>>
    <a href="index.php">Home</a></li>
<li <?php if (stripos($_SERVER['REQUEST_URI'],'upload.php') !== false) {echo 'class="active"';} ?>>
    <a href="upload.php">Upload</a></li>
<li <?php if (stripos($_SERVER['REQUEST_URI'],'search.php') !== false) {echo 'class="active"';} ?>>
    <a href="search.php">Search</a></li>
<li <?php if (stripos($_SERVER['REQUEST_URI'],'pull.php') !== false) {echo 'class="active"';} ?>>
    <a href="pull.php">Download</a></li>

<?php
	// If user has stored API key in cookies
	if (isset($_COOKIE['mapi_key']) == False or ($_COOKIE['mapi_key'] == '') ) {
		echo "<!--?-->";
		if (stripos($_SERVER['REQUEST_URI'],'register.php') !== false) {
			echo '<li class="active">';
		}
		else{
			echo "<li>";
		}
		echo '<a href="register.php">Register</a></li>';
	}
?>

<!-- <li>
    <a href="./daily/">Daily Digest</a></li> -->
<li <?php if (stripos($_SERVER['REQUEST_URI'],'doc.php') !== false) {echo 'class="active"';} ?>>
    <a href="doc.php">API</a></li>
<li <?php if (stripos($_SERVER['REQUEST_URI'],'about.php') !== false) {echo 'class="active"';} ?>>
    <a href="about.php">About</a></li>

                                </ul>
<?php
	if ( !isset($_COOKIE['mapi_key']) || ( $_COOKIE['mapi_key'] == '' )) { echo " <div class=\"nav pull-right\">
			          <form class=\"navbar-form navbar-right\" method=post action=\"auth.php\" >
				            <input class=\"form-control\" type=\"text\" placeholder=\"API Key\" aria-label=\"login\" name=api_key>
				            <button class=\"btnbtn-small  btn-success \" type=\"submit\">Login</button>
			          </form>
                                </div>
				";
	}
	else {
		echo " <div class=\"nav pull-right\">
                                  <form class=\"navbar-form navbar-right\" method=post action=\"auth.php\" >
					    <input type=\"hidden\" name=\"logout\" value=\"logout\">
                                            <button class=\"btn btn-small btn-success \" type=\"submit\">Log out</button>
                                  </form>
                                </div>
                                ";
		
	}
?>
                        </div>
                </div>
    </div>
</div>


