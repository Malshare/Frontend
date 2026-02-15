
	<?php if(count(get_included_files()) ==1) header("Location:index.php") ?>

	<div id="footer">
		<div class="container text-center">
			<?php $footer_locale = i18n_lang_value(); ?>
			<p class="credit">	&copy; <?php echo t('footer.copyright', array('year' => date('Y'))); ?>  | 
				<a href="<?php echo htmlspecialchars(i18n_url_with_lang('tos.php', $footer_locale), ENT_QUOTES, 'UTF-8'); ?>"> <?php echo h('footer.tos'); ?> </a> | 
				<a href="<?php echo htmlspecialchars(i18n_url_with_lang('sitemap.php', $footer_locale), ENT_QUOTES, 'UTF-8'); ?>"> <?php echo h('footer.sitemap'); ?></a> | 
				<a href="https://twitter.com/mal_share?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-show-count="false"><?php echo h('footer.follow'); ?></a><script async src="//platform.twitter.com/widgets.js"></script>
			</p>
		</div>
	</div>
