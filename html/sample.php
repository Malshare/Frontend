<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
    <head>
        <?php include('header.php'); ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script src="./js/vt-augment.min.js" async defer></script>

    
    <link href="./css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
        body {
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
        }
      #push,
      #footer {
        height: 60px;
      }    
        .form-signin {
            max-width: 300px;
            padding: 19px 29px 29px;
            margin: 0 auto 20px;
            background-color: #fff;
            border: 1px solid #e5e5e5;
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
            border-radius: 5px;
                -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
        }
        .form-signin .form-signin-heading,
        .form-signin .checkbox {
            margin-bottom: 10px;
        }
        .form-signin input[type="text"],
        .form-signin input[type="password"] {
            font-size: 16px;
            height: auto;
            margin-bottom: 15px;
            padding: 7px 9px;
        }
    
    </style>
    <link href="./css/sticky-footer-navbar.css" rel="stylesheet">

    </head>

    <body>

<?php include('nav.php') ?>


<script type="text/javascript">
    function ShowLoading(e) {
    setTimeout(function(){
       window.location.reload(1);
    }, 20000);

        var div = document.createElement('div');
        var img = document.createElement('img');
    
        var pendingMsg = <?php echo json_encode(t('sample.pending')); ?>;
        div.innerHTML = "<br /> <br /><h1>" + pendingMsg + "</h1><br />";

        img.src = 'images/ajax-loader.gif';
        div.style.cssText = 'position: fixed; top: 5%; left: 40%; z-index: 5000; width: 422px; text-align: center;';
        div.appendChild(img);
        document.body.appendChild(div);
        return true;
        // These 2 lines cancel form submission, so only use if needed.
        //window.event.cancelBubble = true;
        //e.stopPropagation();
    }
</script>
        <div class="container">            
            <div class="jumbotron">
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
            echo '<br /> <center><p class="lead">' . h('sample.missing_hash') . '</p></center>';    
            die();
        }
    } else {
        echo '
        <form method=post action=sample.php?' .  $_SERVER['QUERY_STRING'] .' class="form-signin">
            <h2 class="form-signin-heading">' . h('sample.captcha_title') . '</h2>
                <center>
                        <div class="g-recaptcha" data-sitekey="6LfippkUAAAAAG9CeuGbV6Yev1FoCMAQzVyPLfE7"></div>
                        <br />
                <button class="btn btn-small btn-primary" type="submit">' . h('sample.submit') . '</button>
                </center>
        </form>';
        }
?>

            
            
            </div>            
        </div> 
            
    

<?php
include_once('footer.php');
?>

            
  </body>
</html>
