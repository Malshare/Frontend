<?php
// Soft 404 page — returns HTTP 200 but indicates a not-found resource to users
http_response_code(200);
header('X-Soft-404: 1');
header('X-Robots-Tag: noindex, follow');

// Include standard site header/footer for consistent look
if (file_exists(dirname(__FILE__) . '/header.php')) {
    include dirname(__FILE__) . '/header.php';
}
if (file_exists(dirname(__FILE__) . '/nav.php')) {
    include dirname(__FILE__) . '/nav.php';
}
?>
<!DOCTYPE html>
<div class="container" style="margin-top: 30px;">

<h2>Terms of Service</h2>
<h3>1. Terms</h3>
<p>By accessing the website at <a href="https://www.malshare.com">https://www.malshare.com</a>, you are agreeing to be bound by these terms of service, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws. If you do not agree with any of these terms, you are prohibited from using or accessing this site. The materials contained in this website are protected by applicable copyright and trademark law.</p>
<h3>2. Use License</h3>
<ol type="a">
   <li>Permission is granted to download materials on MalShare's website for both personal, academic and commercial uses.  Use for commercial purposes requires notification to the site administrator. This is the grant of a license, not a transfer of title, and under this license you may not:
   <ol type="i">
       <li>remove any copyright or other proprietary notations from the materials; or</li>
       <li>reuse site for distribution to third parties</li>
       <li>share provided API keys with third parties</li>
       <li>redistribute content without crediting MalShare as the originating source</li>
   </ol>
    </li>
   <li>This license shall automatically terminate if you violate any of these restrictions and may be terminated by MalShare at any time. Upon terminating your viewing of these materials or upon the termination of this license, you must destroy any downloaded materials in your possession whether in electronic or printed format.</li>
</ol>
<h3>3. Disclaimer</h3>
<ol type="a">
   <li>The materials on MalShare's website are provided on an 'as is' basis. MalShare makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</li>
   <li>Further, MalShare does not warrant or make any representations concerning the accuracy, likely results, or reliability of the use of the materials on its website or otherwise relating to such materials or on any sites linked to this site.</li>
   <li>Site administrators may revoke users API keys at any time and without notice.</li>
</ol>
<h3>4. Limitations</h3>
<p>In no event shall MalShare or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on MalShare's website, even if MalShare or a MalShare authorized representative has been notified orally or in writing of the possibility of such damage. Because some jurisdictions do not allow limitations on implied warranties, or limitations of liability for consequential or incidental damages, these limitations may not apply to you.</p>
<h3>5. Accuracy of materials</h3>
<p>The materials appearing on MalShare website could include technical, typographical, or photographic errors. MalShare does not warrant that any of the materials on its website are accurate, complete or current. MalShare may make changes to the materials contained on its website at any time without notice. However MalShare does not make any commitment to update the materials.</p>
<h3>6. Links</h3>
<p>MalShare has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by MalShare of the site. Use of any such linked website is at the user's own risk.</p>
<h3>7. Modifications</h3>
<p>MalShare may revise these terms of service for its website at any time without notice. By using this website you are agreeing to be bound by the then current version of these terms of service.</p>

<h3>8.Security Issues</h3>
<p>Identified security issues should be reported to security@malshare.com via PGP encrypted email and kept confidential between the source and Malshare for a minimum of 90 days.  Any compensation will be forfirt if disclosed publicly without acknoledgement (either via email or written) by a site administator.  MalShare's policy is to provide up to $50 USD compensation, an amount decided by the admin team, for identified vulnerabilities that are disclosed following previously stated guidelines.  </p>

			</div>			
		</div> 

</div>

<?php
if (file_exists(dirname(__FILE__) . '/footer.php')) {
    include dirname(__FILE__) . '/footer.php';
}

?>

			
	
<?php
include_once('footer.php');
?>

  </body>
</html>
