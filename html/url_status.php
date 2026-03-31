<?php
require_once __DIR__ . '/include/i18n.php';

$guid = filter_input(INPUT_GET, 'guid') ?: '';
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
	<head>
        <?php include('header.php'); ?>
	</head>
	<body>

	<?php include('nav.php'); ?>

	<div class="container">
		<div class="jumbotron">
			<h2 class="form-signin-heading"><?php echo h('upload.url_status_title'); ?></h2>
			<p id="url-display" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%"></p>
			<h4 id="status-msg"><?php echo h('upload.url_status_pending'); ?></h4>
			<p id="queue-info" style="display:none"></p>
			<div id="status-spinner" class="progress progress-striped active" style="max-width:300px">
				<div class="bar" style="width:100%"></div>
			</div>
		</div>
	</div>

	<script>
	(function() {
		var guid = <?php echo json_encode($guid); ?>;
		var apiKey = (document.cookie.match(/(?:^|;\s*)mapi_key=([^;]*)/) || [])[1] || '';

		var messages = {
			pending: <?php echo json_encode(t('upload.url_status_pending')); ?>,
			processing: <?php echo json_encode(t('upload.url_status_processing')); ?>,
			finished: <?php echo json_encode(t('upload.url_status_finished')); ?>,
			missing: <?php echo json_encode(t('upload.url_status_missing')); ?>,
			error: <?php echo json_encode(t('upload.url_status_error')); ?>
		};

		function defang(url) {
			return url.replace(/https?:\/\//g, function(m) {
				return m.replace('http', 'hxxp');
			}).replace(/\./g, '[.]');
		}

		function checkStatus() {
			fetch('api.php?api_key=' + encodeURIComponent(apiKey) + '&action=download_url_check&guid=' + encodeURIComponent(guid))
			.then(function(r) { return r.json(); })
			.then(function(data) {
				if (data.error) {
					document.getElementById('status-msg').textContent = messages.error + ': ' + data.error;
					document.getElementById('status-spinner').style.display = 'none';
					return;
				}

				if (data.url) {
					var el = document.getElementById('url-display');
					var defanged = defang(data.url);
					el.textContent = 'URL: ' + defanged;
					el.title = defanged;
				}

				document.getElementById('status-msg').textContent = messages[data.status] || data.status;

				var queueEl = document.getElementById('queue-info');
				if (data.status === 'pending' && data.queue_position !== undefined) {
					queueEl.style.display = '';
					queueEl.textContent = data.queue_position === 0
						? 'Next in queue'
						: data.queue_position + ' job' + (data.queue_position === 1 ? '' : 's') + ' ahead in queue';
				} else {
					queueEl.style.display = 'none';
				}

				if (data.status === 'finished') {
					document.getElementById('status-spinner').style.display = 'none';
					window.location.href = 'search.php?query=' + encodeURIComponent('source:' + data.url);
				} else if (data.status === 'missing') {
					document.getElementById('status-spinner').style.display = 'none';
				} else {
					setTimeout(checkStatus, 3000);
				}
			})
			.catch(function(e) {
				document.getElementById('status-msg').textContent = messages.error;
				setTimeout(checkStatus, 5000);
			});
		}

		if (guid && apiKey) {
			checkStatus();
		}
	})();
	</script>

	<?php include_once('footer.php'); ?>

  </body>
</html>
