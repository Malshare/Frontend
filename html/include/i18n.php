<?php

function i18n_supported_locales()
{
    return array('en', 'fr', 'de');
}

function i18n_default_locale()
{
    return 'en';
}

function i18n_get_locale()
{
    static $locale = null;
    if ($locale !== null) {
        return $locale;
    }

    if (isset($_COOKIE['mlang'])) {
        $cookie_lang = strtolower(substr($_COOKIE['mlang'], 0, 2));
        if (in_array($cookie_lang, i18n_supported_locales(), true)) {
            $locale = $cookie_lang;
            return $locale;
        }
    }

    $accept = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    foreach (explode(',', $accept) as $entry) {
        $code = strtolower(trim(substr($entry, 0, 2)));
        if ($code !== '' && in_array($code, i18n_supported_locales(), true)) {
            $locale = $code;
            return $locale;
        }
    }

    $locale = i18n_default_locale();
    return $locale;
}

function i18n_strings()
{
    static $strings = null;
    if ($strings !== null) {
        return $strings;
    }

    $base_dir = dirname(__FILE__) . '/../i18n';
    $default_path = $base_dir . '/en.php';
    $default_strings = file_exists($default_path) ? include $default_path : array();

    $locale = i18n_get_locale();
    $locale_path = $base_dir . '/' . $locale . '.php';
    if ($locale !== 'en' && file_exists($locale_path)) {
        $locale_strings = include $locale_path;
        $strings = array_replace_recursive($default_strings, $locale_strings);
    } else {
        $strings = $default_strings;
    }

    return $strings;
}

function t($key, array $vars = array())
{
    $value = i18n_strings();
    $parts = explode('.', $key);
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $key;
        }
        $value = $value[$part];
    }

    if (!is_string($value)) {
        return $key;
    }

    if (!empty($vars)) {
        $replacements = array();
        foreach ($vars as $var_key => $var_value) {
            $replacements['{{' . $var_key . '}}'] = $var_value;
        }
        $value = strtr($value, $replacements);
    }

    return $value;
}

function h($key, array $vars = array())
{
    return htmlspecialchars(t($key, $vars), ENT_QUOTES, 'UTF-8');
}

function i18n_url_with_lang($url, $lang)
{
    $parts = parse_url($url);
    $path = isset($parts['path']) ? $parts['path'] : '';
    $query = isset($parts['query']) ? $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';

    $params = array();
    if ($query !== '') {
        parse_str($query, $params);
    }

    unset($params['lang']);

    $new_query = http_build_query($params);
    $new_url = $path;
    if ($new_query !== '') {
        $new_url .= '?' . $new_query;
    }
    if ($fragment !== '') {
        $new_url .= '#' . $fragment;
    }

    return $new_url;
}

function i18n_current_url_with_lang($lang)
{
    $current = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    return i18n_url_with_lang($current, $lang);
}

function i18n_lang_value()
{
    return i18n_get_locale();
}

?>
