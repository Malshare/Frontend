<!DOCTYPE html>
<html lang="en">
	<head>
        <?php include('header.php'); ?>

	</head>

	<body>
        <?php include('nav.php') ?>

		<div class="container">			
			<div class="jumbotron">
				<h2>API Documentation</h2>
				The API is provided for the registered users to allow for accessing of files and data stored within out dataset.<br />
			</div>

			<h3>Tools</h3>
			<table class="table">
			  <tr>
			    <th>Language</th>
			    <th>Developer</th>
			    <th>Link</th>
			  </tr>
			  <tr>
			    <td>Python</td>
			    <td><a href="https://twitter.com/silascutler">@SilasCutler</a></td>
			    <td><a href="https://github.com/Malshare/MalShare-Toolkit">Github.com/MalShare/MalShare-Toolkit</a></td>
			  </tr>
			  <tr>
			    <td>.NET</td>
			    <td><a href="https://twitter.com/AlexBK1996">@AlexBK1996</a></td>
			    <td><a href="https://github.com/Malshare/MalShare.NET">Github.com/MalShare/MalShare.NET</a></td>
			  </tr>
			  <tr>
			    <td>Go</td>
			    <td><a href="https://twitter.com/MonaxGT">@MonaxGT</a></td>
			    <td><a href="https://github.com/MonaxGT/gomalshare">Github.com/MonaxGT/gomalshare</a></td>
			  </tr>
			  <tr>
			    <td>Java</td>
			    <td><a href="https://twitter.com/LibraAnalysis">@LibraAnalysis</a></td>
			    <td><a href="https://github.com/ThisIsLibra/MalShareApi</a></td>
			  </tr>
				

			</table>


			<h3> API Endpoints </h3>
					
			<table class="table">
			  <tr>
			    <th>Request Type</th>
			    <th>URL Path</th>
			    <th>Description </th> 
			    <th>Output Format</th>
			  </tr>
				<a name="getlist"></a>				
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getlist</td>
			    <td>List hashes from the past 24 hours</td>
			    <td>JSON</td>
			  </tr>
				<a name="getlistraw"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getlistraw</td>
			    <td>List hashes from the past 24 hours </td>
			    <td>Raw Text List</td>
			  </tr>
				<a name="getlistraw"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getsources</td>
			    <td>List of sample sources from the past 24 hours</td>
			    <td>JSON</td>
			  </tr>
				<a name="getsourcesraw"></a>				
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getsourcesraw</td>
			    <td>List of sample sources from the past 24 hours</td>
			    <td>Raw Text List</td>
			  </tr>
				<a name="getfile"></a>
				<a name="download"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getfile&amp;hash=[HASH]</td>
			    <td>Download File</td>
			    <td>Raw data</td>
			  </tr>
				<a name="details"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=details&amp;hash=[HASH]</td>
			    <td>GET stored file details</td>
			    <td>JSON</td>
			  </tr>
			  <tr>
			    <td class="hash_font">POST</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=hashlookup</td>
			    <td>Supply an array of hex-encoded hashes in a POST field named <span class="hash_font">hashes</span>.</td>
			    <td>JSON</td>
			  </tr>
				<a name="list"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=type&amp;type=[FILE TYPE] </td>
			    <td>List MD5/SHA1/SHA256 hashes of a specific type from the past 24 hours</td>
			    <td>JSON</td>
			  </tr>
				<a name="search"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=search&amp;query=[SEARCH QUERY] </td>
			    <td>Search sample hashes, sources and file names</td>
			    <td>Raw data</td>
			  </tr>
				<a name="gettypes"></a>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=gettypes</td>
			    <td>Get list of file types & count from the past 24 hours</td>
			    <td>JSON</td>
			  </tr>  
				<a name="upload"></a>
			  <tr>
			    <td class="hash_font">POST</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=upload </td>
			    <td>Upload using FormData field "upload".  Uploading files temporarily increases a users quota.</td>
			    <td></td>
			  </tr>
			<a name="getlimit"></a> 
				
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getlimit </td>
			    <td>GET allocated number of API key requests per day and remaining</td>
			    <td>Raw data</td>
			  </tr>
			<a name="download_url"></a> 
				
			  <tr>
			    <td class="hash_font">POST</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=download_url </td>
			    <td>
			        Perform URL download and add result to sample collection.<br/>
			        Pass URL to be downloaded in POST body with name <span class="hash_font">url</span><br/>
			        Pass <span class="hash_font">1</span> to a variable with name <span class="hash_font">recursive</span> to
			        enable crawling of the specified URL.<br/>
			        <br/>
			        Example:<br/>
			        <span class="hash_font">
			            curl -X POST -F "url=http://example.com" "http://malshare.com/api.php?api_key=1234&action=download_url"
			        </span>
			    </td>
			    <td>JSON</td>
				</tr>
			<a name="download_url_check"></a> 

			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=download_url_check&amp;guid=[GUID] </td>
			    <td>
			        Check status of download task via GUID. Response contains one of the following status values:
			        <ul>
			            <li><span class="hash_font">missing</span> task with specified GUID does not exist</li>
			            <li><span class="hash_font">pending</span> task was submitted but not picked up yet</li>
			            <li><span class="hash_font">processing</span> download in progress</li>
			            <li><span class="hash_font">finished</span> job finished</li>
			        </ul>
			    </td>
			    <td>JSON</td>
			  </tr>

			 </table>
		</div> 
	
	<?php include_once('footer.php'); ?>

	</body>
</html>

