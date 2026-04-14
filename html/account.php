<?php require_once __DIR__ . '/include/i18n.php'; ?>
<?php include("server_includes.php"); ?>
<?php
$share = new ServerObject();
if (!isset($_COOKIE['mapi_key']) || $_COOKIE['mapi_key'] === '') {
    header("Location: index.php");
    exit();
}
$user = new UserObject($share->sql, $_COOKIE['mapi_key'], true);
if (!$user->ready) {
    header("Location: index.php");
    exit();
}

$quota = $share->get_user_quota($_COOKIE['mapi_key']);
$limit = $quota ? $quota['limit'] : 0;
$remaining = $quota ? $quota['remaining'] : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <?php include('header.php'); ?>
</head>

<body>
<?php include('nav.php') ?>
<div class="container">
    <div class="hero-unit">
        <h2><?php echo h('account.title'); ?></h2>
        <table class="table table-striped">
            <tr>
                <td><b><?php echo h('account.daily_limit'); ?></b></td>
                <td><?php echo $limit; ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('account.remaining'); ?></b></td>
                <td><?php echo $remaining; ?></td>
            </tr>
        </table>
    </div>
</div>

<br/>

<?php include_once('footer.php'); ?>

</body>
</html>
