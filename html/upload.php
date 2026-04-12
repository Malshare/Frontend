<?php
require_once __DIR__ . '/include/i18n.php';

$errorMessage = '';
if ((array_key_exists('fsample', $_FILES) && ($_FILES['fsample']))) {
    if ($_FILES["fsample"]["size"] > 26214400) {
        $errorMessage = t('upload.too_large_error');
    } else {
        include("server_includes.php");

        $res = (new ServerObject())->upload_sample($_FILES['fsample']);
        if ($res['type'] === 'error') {
            $errorMessage = $res['message'];
        } elseif ($res['type'] === 'success') {
            header("Location:sample.php?action=detail&hash=" . rawurlencode($res['sha256']));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <?php include('header.php'); ?>
</head>
<body>

<script>
    function validate() {
        var size = 26214400;
        var file_size = document.getElementById('fsample').files[0].size;
        if (file_size >= size) {
            var tooLargeMsg = <?php echo json_encode(t('upload.too_large')); ?>;
            alert(tooLargeMsg);
            return false;
        }
    }
</script>

<?php include('nav.php'); ?>

<div class="container">
    <div class="jumbotron">
        <?php
        if ($errorMessage) {
            echo '<center><font color="red"><h4 class="form-signin-heading">' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</h2></font></center>';
        }
        ?>

        <form method=post action=upload.php class="form-signin" enctype="multipart/form-data">
            <h2 class="form-signin-heading"><?php echo h('upload.title'); ?></h2>
            <p><i><?php echo h('upload.notice'); ?> </i></p>
            <input type="file" name="fsample" id="fsample" class="fileupload" data-icon="false">
            <button class="btn btn-small btn-primary" onClick="return validate() && showScroll()"
                    type="submit"><?php echo h('upload.submit'); ?></button>
        </form>
    </div>

    <div class="jumbotron">
        <h2 class="form-signin-heading"><?php echo h('upload.url_title'); ?></h2>
        <?php if (isset($_COOKIE['mapi_key']) && $_COOKIE['mapi_key'] !== ''): ?>
            <p><i><?php echo h('upload.url_notice'); ?></i></p>
            <div id="url-result"></div>
            <form id="url-form" class="form-signin" onsubmit="return submitUrl()">
                <input type="url" id="url_input" class="input-block-level"
                       placeholder="<?php echo h('upload.url_placeholder'); ?>" required>
                <button class="btn btn-small btn-primary" type="submit"><?php echo h('upload.url_submit'); ?></button>
            </form>
            <script>
                function submitUrl() {
                    var url = document.getElementById('url_input').value;
                    var apiKey = '<?php echo htmlspecialchars($_COOKIE['mapi_key'], ENT_QUOTES, 'UTF-8'); ?>';
                    var formData = new FormData();
                    formData.append('url', url);
                    fetch('api.php?api_key=' + encodeURIComponent(apiKey) + '&action=download_url', {
                        method: 'POST',
                        body: formData
                    })
                        .then(function (r) {
                            return r.json();
                        })
                        .then(function (data) {
                            if (data.error) {
                                document.getElementById('url-result').innerHTML = '<font color="red"><h4>' + data.error + '</h4></font>';
                            } else {
                                window.location.href = 'url_status.php?guid=' + encodeURIComponent(data.guid);
                            }
                        })
                        .catch(function (e) {
                            document.getElementById('url-result').innerHTML = '<font color="red"><h4>Error: ' + e + '</h4></font>';
                        });
                    return false;
                }
            </script>
        <?php else: ?>
            <p><i><?php echo h('upload.url_login_required'); ?></i></p>
        <?php endif; ?>
    </div>
</div>

<?php include_once('footer.php'); ?>

</body>
</html>
