<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include('header.php'); ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script src="./js/vt-augment.min.js" async defer></script>
    </head>

    <body>

<?php include('nav.php') ?>


<script type="text/javascript">
    function showPendingOverlay() {
        var overlay = document.createElement('div');
        overlay.className = 'ms-loading-overlay';
        overlay.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading…</span></div><div class="ms-loading-text">Pending Analysis…</div>';
        document.body.appendChild(overlay);
        setTimeout(function(){ window.location.reload(1); }, 20000);
    }
</script>
        <div class="container py-4">            
            <div class="ms-card">
<?php
    include("server_includes.php");
    require_once "recaptchalib.php";


    // Captcha Check skip for logged in users:
    $share = new ServerObject();
    $getDetails = false;

        if (array_key_exists('mapi_key', $_COOKIE) && $_COOKIE['mapi_key'] != "" ){
        $uuser = $share->login();
        if ( $uuser->ready == true ) {
            $getDetails = true;
        }
    }

     $secret = getenv('MALSHARE_RECAPTCHA_SECRET');
     if ($secret == "DISABLED") {
         $getDetails= true;
     } else{
         $g_recaptcha = filter_input(INPUT_POST, 'g-recaptcha-response') ?: '';
         if ($g_recaptcha !== '' && strlen($g_recaptcha) > 5) {
            $reCaptcha = new ReCaptcha($secret);

            $response = $reCaptcha->verifyResponse(
                "malshare.com",
                $g_recaptcha
            );
            if ($response != null && $response->success) {
                $getDetails = true;
            }

         }
     }


    if ( $getDetails == true ) {
        if($_GET["hash"]!="" && $_GET["action"]=="detail") {
            echo $share->get_details();
        }
        else{
            echo '<div class="text-center py-4"><p class="lead text-body-secondary">Please enter request with a hash</p></div>';    
            die();
        }
    } else {
        echo '
        <div class="ms-form-card">
        <form method="post" action="sample.php?' .  $_SERVER['QUERY_STRING'] .'">
                <h2><i class="bi bi-shield-check me-2"></i>Captcha Check</h2>
                <div class="text-center">
                        <div class="d-inline-block mb-3"><div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div></div>
                        <br />
                        <button class="btn btn-primary" type="submit">Submit</button>
                </div>
        </form>
        </div>';
        }
?>

            
            
            </div>            
        </div> 
            
    

<?php
include_once('footer.php');
?>

            
  </body>
</html>

