<?php
$i18n_path = __DIR__ . '/include/i18n.php';
$use_i18n = false;
if (file_exists($i18n_path)) {
    include_once $i18n_path;
    $use_i18n = function_exists('t') && function_exists('i18n_lang_value');
}

if (!$use_i18n) {
    $fallback_path = __DIR__ . '/50x.html';
    if (file_exists($fallback_path)) {
        readfile($fallback_path);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo h('errors.temp_unavailable_title'); ?></title>
    <style>
        body {
            font-family: Tahoma, Verdana, Arial, sans-serif;
        }
    </style>
</head>
<body bgcolor="white" text="black">
<table width="100%" height="100%">
    <tr>
        <td align="center" valign="middle">
            <?php echo t('errors.temp_unavailable_body'); ?>
        </td>
    </tr>
</table>
</body>
</html>
