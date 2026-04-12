<?php require_once __DIR__ . '/include/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <?php include('header.php'); ?>
</head>

<body>
<?php include('nav.php') ?>
<div class="container">
    <div class="hero-unit">
        <center><img src="images/logo_header.png" width="333" height="119" alt="Malshare Logo"></center>
        <?php echo t('about.intro'); ?> <br/><br/>
        <p><?php echo t('about.api_info'); ?> </p>

        <p><?php echo t('about.feature_requests'); ?></p>
        <p><?php echo t('about.blog'); ?></p>
        <p><?php echo t('about.thanks', array('thanks_url' => htmlspecialchars(i18n_url_with_lang('thanks.php', i18n_lang_value()), ENT_QUOTES, 'UTF-8'))); ?></p>
        <br/>
        <center>
            <a href="https://github.com/malshare"><img src="images/github.png" height="20" width="20"> GitHub </a>|<a
                    href="https://twitter.com/mal_share"><img src="images/twitter.png" height="20" width="20">
                Twitter</a>
        </center>

        <div class="row">
            <div class="span5">
                <h3> <?php echo h('about.admin_team'); ?></h3>
                <p><b> Silas Cutler </b> <i> - Founder / Lead Developer</i> <br/> - <a
                            href="https://twitter.com/silascutler?ref_src=twsrc%5Etfw" class="twitter-follow-button"
                            data-show-count="false">Follow @silascutler</a>
                    <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
                </p>
                <p><b> Brandon Levene</b> <i> </i> <br/> - <a
                            href="https://twitter.com/SeraphimDomain?ref_src=twsrc%5Etfw" class="twitter-follow-button"
                            data-show-count="false">Follow @SeraphimDomain</a>
                    <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
                </p>
                <p><b> Lars A. Wallenborn </b> <i> - Developer</i> <br/> - <a
                            href="https://twitter.com/larsborn?ref_src=twsrc%5Etfw" class="twitter-follow-button"
                            data-show-count="false">Follow @larsborn</a>
                    <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
                </p>
                <p><b> Alexandru Constantin </b> <i> - Client Developer</i> <br/> - <a
                            href="https://twitter.com/AlexBK1996?twsrc%5Etfw" class="twitter-follow-button"
                            data-show-count="false">Follow @AlexBK1996</a>
                    <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
                </p>
            </div>
            <div>
                <center><h3><?php echo h('about.partners'); ?> </h3>
                    <a href="https://www.zemana.com/"><img src="images/zemana_logo.png" width="182" height="140"
                                                           alt="Zemana"></a>
                    <a href="https://www.virussamples.com/"><img src="images/malware_virus_samples_logo2.png"
                                                                 width="182" height="120" alt="VirusSamples"></a>

                    <br/>
                    <img src="images/ET-PP-Logo.png" width="214" height="72" alt="Emerging Threats / Proofpoint">
                    <img src="images/12-1362_Crowd_Strike_Logo_Red_D0_01.gif" height="134" width="209"
                         alt="CrowdStrike">
                    <img src="images/2uB3xrjd.jpg" height="100" width="100" alt="QuadraNet">
                    <img src="images/TEHTRIS.png" height="100" width="206" alt="Tehtris">
                    <img src="images/TPSC.png" height="100" width="100" alt="The PC Security Channel">
                    <img src="images/farsight-logo.svg" height="200" width="200" alt="Farsight Security">
                </center>
            </div>
        </div>
    </div>
</div>

<br/>

<?php
include_once('footer.php');
?>

</body>
</html>
