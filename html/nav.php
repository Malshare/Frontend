<?php
	error_reporting(E_ALL & ~E_NOTICE);

	if(count(get_included_files()) ==1) {
		 header("Location:index.php");
	}
?> 

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
	<div class="container-fluid">
		<a class="navbar-brand fw-bold" href="/" name="top"><span class="text-white">Mal</span><span class="text-light opacity-75">Share</span></a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="mainNavbar">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				<li class="nav-item">
					<a class="nav-link <?php if (stripos($_SERVER['REQUEST_URI'],'index.php') !== false) echo 'active'; ?>" href="index.php">Home</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php if (stripos($_SERVER['REQUEST_URI'],'upload.php') !== false) echo 'active'; ?>" href="upload.php">Upload</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php if (stripos($_SERVER['REQUEST_URI'],'search.php') !== false) echo 'active'; ?>" href="search.php">Search</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php if (stripos($_SERVER['REQUEST_URI'],'pull.php') !== false) echo 'active'; ?>" href="pull.php">Download</a>
				</li>

<?php
	if (isset($_COOKIE['mapi_key']) == False or ($_COOKIE['mapi_key'] == '') ) {
		echo '<li class="nav-item">';
		echo '<a class="nav-link';
		if (stripos($_SERVER['REQUEST_URI'],'register.php') !== false) echo ' active';
		echo '" href="register.php">Register</a></li>';
	}
?>

				<li class="nav-item">
					<a class="nav-link <?php if (stripos($_SERVER['REQUEST_URI'],'doc.php') !== false) echo 'active'; ?>" href="doc.php">API</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php if (stripos($_SERVER['REQUEST_URI'],'about.php') !== false) echo 'active'; ?>" href="about.php">About</a>
				</li>
			</ul>

<?php
	if ( !isset($_COOKIE['mapi_key']) || ( $_COOKIE['mapi_key'] == '' )) {
		echo '<form class="d-flex" method="post" action="auth.php">
			<input class="form-control form-control-sm me-2" type="text" placeholder="API Key" aria-label="login" name="api_key">
			<button class="btn btn-sm btn-success" type="submit">Login</button>
		</form>';
	} else {
		echo '<form class="d-flex" method="post" action="auth.php">
			<input type="hidden" name="logout" value="logout">
			<button class="btn btn-sm btn-outline-light" type="submit">Log out</button>
		</form>';
	}
?>
		</div>
	</div>
</nav>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>