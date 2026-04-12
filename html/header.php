<?php
require_once __DIR__ . '/include/i18n.php';
?>

<meta charset="utf-8">
<title><?php echo h('site.name'); ?></title>
<meta name="description" content="<?php echo h('meta.description'); ?>">

<link href="./css/malshare.css" rel="stylesheet">
<link href="./css/bootstrap.css" rel="stylesheet">
<link href="./css/sticky-footer-navbar.css" rel="stylesheet">
<link href="./css/popup.css" rel="stylesheet">

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-4WK6RGYZEY"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());

    gtag('config', 'G-4WK6RGYZEY');
</script>
