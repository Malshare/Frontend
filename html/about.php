<!DOCTYPE html>
<html lang="en">
	<head>
		<?php include('header.php'); ?>
	</head>

	<body>
	<?php include('nav.php') ?>

	<div class="container py-4">
		<!-- Hero -->
		<div class="ms-hero text-center mb-4">
			<img src="images/logo_header.png" width="333" height="119" alt="Malshare Logo" class="mb-3">
			<p class="lead mb-0">A collaborative, community-driven public malware repository building tools to benefit the security community at large.</p>
		</div>

		<!-- About -->
		<div class="ms-card">
			<p>Not all files in our system are malicious and our data feeds are considered as is. We offer free public API keys. Standard keys allow 2000 API calls per day (including downloading samples, details lookup and search). If you require more, contact <a href="mailto:admin@MalShare.com">admin@MalShare.com</a> for further assistance.</p>
			<p>Feature requests, bug reports and any other issues can be reported <a href="https://github.com/Malshare/MalShare/issues">here</a>.</p>
			<p>Large updates and general information can be found on our <a href="https://malshare.blogspot.com/">blog</a>.</p>
			<p class="mb-0">A list of those who have helped secure MalShare through security reports can be found <a href="thanks.php">here</a>.</p>
			<hr>
			<div class="text-center">
				<a href="https://github.com/malshare" class="btn btn-outline-dark btn-sm me-2"><i class="bi bi-github me-1"></i>GitHub</a>
				<a href="https://twitter.com/mal_share" class="btn btn-outline-primary btn-sm"><i class="bi bi-twitter me-1"></i>Twitter</a>
			</div>
		</div>

		<!-- Team -->
		<div class="ms-card">
			<h5 class="ms-section-title"><i class="bi bi-people me-2"></i>Admin Team</h5>
			<p><strong>Silas Cutler</strong> <span class="text-body-secondary">— Founder / Lead Developer</span><br><a href="https://twitter.com/silascutler?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-show-count="false">Follow @silascutler</a><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script></p>
			<p><strong>Brandon Levene</strong><br><a href="https://twitter.com/SeraphimDomain?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-show-count="false">Follow @SeraphimDomain</a></p>
			<p><strong>Lars A. Wallenborn</strong> <span class="text-body-secondary">— Developer</span><br><a href="https://twitter.com/larsborn?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-show-count="false">Follow @larsborn</a></p>
			<p class="mb-0"><strong>Alexandru Constantin</strong> <span class="text-body-secondary">— Client Developer</span><br><a href="https://twitter.com/AlexBK1996?twsrc%5Etfw" class="twitter-follow-button" data-show-count="false">Follow @AlexBK1996</a></p>
		</div>

		<!-- Partners -->
		<div class="ms-card mt-4">
			<h5 class="ms-section-title text-center"><i class="bi bi-handshake me-2"></i>Partners</h5>
			<div class="d-flex flex-wrap justify-content-center align-items-center gap-4 py-3">
				<a href="https://www.zemana.com/"><img src="images/zemana_logo.png" class="ms-partner-logo" alt="Zemana"></a>
				<a href="https://www.virussamples.com/"><img src="images/malware_virus_samples_logo2.png" class="ms-partner-logo" alt="VirusSamples"></a>
				<img src="images/ET-PP-Logo.png" class="ms-partner-logo" alt="Emerging Threats / Proofpoint">
				<img src="images/12-1362_Crowd_Strike_Logo_Red_D0_01.gif" class="ms-partner-logo" alt="CrowdStrike">
				<img src="images/TEHTRIS.png" class="ms-partner-logo" alt="Tehtris">
				<img src="images/TPSC.png" class="ms-partner-logo" alt="The PC Security Channel">
				<img src="images/farsight-logo.svg" class="ms-partner-logo" alt="Farsight Security">
			</div>
		</div>
	</div>

<?php
include_once('footer.php');
?>

  </body>
</html>

