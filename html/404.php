<?php
require_once __DIR__ . '/include/i18n.php';
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
        <div class="page-header">
            <h1><?php echo h('errors.page_not_found'); ?></h1>
        </div>

        <p><?php echo h('errors.not_found_body'); ?></p>

        <ul>
            <li><a href="index.php"><?php echo h('errors.home'); ?></a></li>
            <li><a href="search.php"><?php echo h('errors.search_samples'); ?></a></li>
            <li><a href="upload.php"><?php echo h('errors.upload_sample'); ?></a></li>
            <li><a href="sampleshare.php"><?php echo h('errors.browse_samples'); ?></a></li>
        </ul>

        <form action="search.php" method="get" class="form-inline" style="margin-top: 15px;">
            <div class="form-group">
                <label for="query" class="sr-only"><?php echo h('errors.search_label'); ?></label>
                <input type="text" name="query" id="query" class="form-control"
                       placeholder="<?php echo h('errors.search_placeholder'); ?>"/>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo h('errors.search'); ?></button>
        </form>

        <hr/>
        <p><?php echo t('errors.contact_admin'); ?></p>
    </div>

<?php
if (file_exists(dirname(__FILE__) . '/footer.php')) {
    include dirname(__FILE__) . '/footer.php';
}

// End of soft_404.php
