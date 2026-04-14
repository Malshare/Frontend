<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once __DIR__ . '/include/i18n.php';

if (count(get_included_files()) == 1) {
    header("Location:index.php");
}

$locales = i18n_supported_locales();
$current_locale = i18n_lang_value();
?>

<div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a class="brand"
               href="<?php echo htmlspecialchars(i18n_url_with_lang('index.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"
               name="top">&nbsp;&nbsp;&nbsp;&nbsp;<b>Mal</b>Share</a>
            <div class="nav-collapse collapse">
                <ul class="nav">

                    <li <?php if (stripos($_SERVER['REQUEST_URI'], 'index.php') !== false) {
                        echo 'class="active"';
                    } ?>>
                        <a href="<?php echo htmlspecialchars(i18n_url_with_lang('index.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"><?php echo h('nav.home'); ?></a>
                    </li>
                    <li <?php if (stripos($_SERVER['REQUEST_URI'], 'upload.php') !== false) {
                        echo 'class="active"';
                    } ?>>
                        <a href="<?php echo htmlspecialchars(i18n_url_with_lang('upload.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"><?php echo h('nav.upload'); ?></a>
                    </li>
                    <li <?php if (stripos($_SERVER['REQUEST_URI'], 'search.php') !== false) {
                        echo 'class="active"';
                    } ?>>
                        <a href="<?php echo htmlspecialchars(i18n_url_with_lang('search.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"><?php echo h('nav.search'); ?></a>
                    </li>
                    <li <?php if (stripos($_SERVER['REQUEST_URI'], 'pull.php') !== false) {
                        echo 'class="active"';
                    } ?>>
                        <a href="<?php echo htmlspecialchars(i18n_url_with_lang('pull.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"><?php echo h('nav.download'); ?></a>
                    </li>

                    <?php
                    // If user has stored API key in cookies
                    if (isset($_COOKIE['mapi_key']) == False or ($_COOKIE['mapi_key'] == '')) {
                        echo "<!--?-->";
                        if (stripos($_SERVER['REQUEST_URI'], 'register.php') !== false) {
                            echo '<li class="active">';
                        } else {
                            echo "<li>";
                        }
                        echo '<a href="' . htmlspecialchars(i18n_url_with_lang('register.php', $current_locale), ENT_QUOTES, 'UTF-8') . '">' . h('nav.register') . '</a></li>';
                    }
                    ?>

                    <!-- <li>
                        <a href="./daily/">Daily Digest</a></li> -->
                    <li <?php if (stripos($_SERVER['REQUEST_URI'], 'doc.php') !== false) {
                        echo 'class="active"';
                    } ?>>
                        <a href="<?php echo htmlspecialchars(i18n_url_with_lang('doc.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"><?php echo h('nav.api'); ?></a>
                    </li>
                    <li <?php if (stripos($_SERVER['REQUEST_URI'], 'about.php') !== false) {
                        echo 'class="active"';
                    } ?>>
                        <a href="<?php echo htmlspecialchars(i18n_url_with_lang('about.php', $current_locale), ENT_QUOTES, 'UTF-8'); ?>"><?php echo h('nav.about'); ?></a>
                    </li>


                </ul>

                <?php
                if (!isset($_COOKIE['mapi_key']) || ($_COOKIE['mapi_key'] == '')) {
                    echo " <div class=\"nav pull-right\">
			          <form class=\"navbar-form navbar-right\" method=post action=\"auth.php\" >
					            <input class=\"form-control\" type=\"text\" placeholder=\"" . h('nav.api_key') . "\" aria-label=\"login\" name=api_key>
					            <button class=\"btnbtn-small  btn-success \" type=\"submit\">" . h('nav.login') . "</button>
			          </form>
                                </div>
				";
                } else {
                    echo " <div class=\"nav pull-right\">
                                  <form class=\"navbar-form navbar-right\" method=post action=\"auth.php\" >
					    <input type=\"hidden\" name=\"logout\" value=\"logout\">
					    <a class=\"btn btn-small btn-success\" href=\"" . htmlspecialchars(i18n_url_with_lang('account.php', $current_locale), ENT_QUOTES, 'UTF-8') . "\">" . h('nav.account') . "</a>
	                                            <button class=\"btn btn-small btn-success \" type=\"submit\">" . h('nav.logout') . "</button>
                                  </form>
                                </div>
                                ";

                }
                ?>
                <ul class="nav pull-right">
                    <li class="navbar-form" style="margin: 0 10px 0 0;">
                        <select class="input-mini" style="width: 58px;"
                                onchange="var opt=this.options[this.selectedIndex]; if (opt && opt.value) { document.cookie = 'mlang=' + opt.getAttribute('data-locale') + '; path=/; max-age=15552000'; window.location = opt.value; }">
                            <?php foreach ($locales as $locale) {
                                $selected = ($locale === $current_locale) ? ' selected="selected"' : '';
                                $url = i18n_current_url_with_lang($locale);
                                echo '<option data-locale="' . htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
