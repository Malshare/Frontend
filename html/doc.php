<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
        <?php include('header.php'); ?>

	</head>

	<body>
        <?php include('nav.php') ?>

		<div class="container">			
			<div class="jumbotron">
				<h2><?php echo h('doc.title'); ?></h2>
				<?php echo t('doc.intro'); ?><br />
			</div>

			<h3><?php echo h('doc.tools'); ?></h3>
			<table class="table">
			  <tr>
			    <th><?php echo h('doc.language'); ?></th>
			    <th><?php echo h('doc.developer'); ?></th>
			    <th><?php echo h('doc.link'); ?></th>
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
			    <td><a href="https://twitter.com/Libranalysis">@Libranalysis</a></td>
			    <td><a href="https://github.com/ThisIsLibra/MalShareApi">Github.com/ThisIsLibra/MalShareApi</a></td>
			  </tr>
			  <tr>
			    <td>Python</td>
			    <td><a href="https://twitter.com/0xDroogy">@0xDroogy</a></td>
			    <td><a href="https://github.com/Droogy/Malget">https://github.com/Droogy/Malget</a></td>
			  </tr>				
                          <tr>
                            <td>Python</td>
                            <td><a href="https://github.com/toys0ldier">@toys0ldier</a></td>
                            <td><a href="https://github.com/toys0ldier/malware_keywords">https://github.com/toys0ldier/malware_keywords</a></td>
                          </tr>
			</table>


			<h3> <?php echo h('doc.endpoints'); ?> </h3>
					
			<table class="table">
			  <tr>
			    <th><?php echo h('doc.request_type'); ?></th>
			    <th><?php echo h('doc.url_path'); ?></th>
			    <th><?php echo h('doc.description'); ?> </th> 
			    <th><?php echo h('doc.output_format'); ?></th>
			  </tr>
				<a name="getlist"></a>				
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getlist</td>
			    <td><?php echo h('doc.desc_getlist'); ?></td>
			    <td>JSON</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getlistraw</td>
			    <td><?php echo h('doc.desc_getlistraw'); ?> </td>
			    <td>Raw Text List</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getsources</td>
			    <td><?php echo h('doc.desc_getsources'); ?></td>
			    <td>JSON</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getsourcesraw</td>
			    <td><?php echo h('doc.desc_getsourcesraw'); ?> </td>
			    <td>Raw Text List</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getfile&amp;hash=[HASH]</td>
			    <td><?php echo h('doc.desc_getfile'); ?></td>
			    <td>Raw data</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=details&amp;hash=[HASH]</td>
			    <td><?php echo h('doc.desc_details'); ?></td>
			    <td>JSON</td>
			  </tr>
			  <tr>
			    <td class="hash_font">POST</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=hashlookup</td>
			    <td><?php echo t('doc.desc_hashlookup'); ?></td>
			    <td>JSON</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=type&amp;type=[FILE TYPE] </td>
			    <td><?php echo h('doc.desc_type'); ?></td>
			    <td>JSON</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=search&amp;query=[SEARCH QUERY] </td>
			    <td><?php echo h('doc.desc_search'); ?></td>
			    <td>Raw data</td>
			  </tr>
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=gettypes</td>
			    <td><?php echo h('doc.desc_gettypes'); ?></td>
			    <td>JSON</td>
			  </tr>  
			  <tr>
			    <td class="hash_font">POST</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=upload </td>
			    <td><?php echo h('doc.desc_upload'); ?></td>
			    <td></td>
			  </tr>		
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getfilenames </td>
			    <td><?php echo h('doc.desc_getfilenames'); ?></td>
			    <td></td>
			  </tr>					  
			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=getlimit </td>
			    <td><?php echo h('doc.desc_getlimit'); ?></td>
			    <td>Raw data</td>
			  </tr>				
			  <tr>
			    <td class="hash_font">POST</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=download_url </td>
			    <td>
			        <?php echo t('doc.download_url_note'); ?>
			    </td>
			    <td>JSON</td>
				</tr>
			<a name="download_url_check"></a> 

			  <tr>
			    <td class="hash_font">GET</td>
			    <td class="hash_font">/api.php?api_key=[API_KEY]&amp;action=download_url_check&amp;guid=[GUID] </td>
			    <td>
			        <?php echo h('doc.download_url_check_note'); ?>
			        <ul>
			            <li><span class="hash_font"><?php echo h('doc.missing'); ?></span> <?php echo h('doc.status_missing'); ?></li>
			            <li><span class="hash_font"><?php echo h('doc.pending'); ?></span> <?php echo h('doc.status_pending'); ?></li>
			            <li><span class="hash_font"><?php echo h('doc.processing'); ?></span> <?php echo h('doc.status_processing'); ?></li>
			            <li><span class="hash_font"><?php echo h('doc.finished'); ?></span> <?php echo h('doc.status_finished'); ?></li>
			        </ul>
			    </td>
			    <td>JSON</td>
			  </tr>

			 </table>
		</div> 
	
	<?php include_once('footer.php'); ?>

	</body>
</html>
