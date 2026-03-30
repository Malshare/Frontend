<?php
require dirname(__FILE__) . '/../vendor/autoload.php';
require_once __DIR__ . '/include/i18n.php';

/* ****************************************** */
/* Norman SampleShare Server Framework        */
/* Version 1.30                               */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* ****************************************** */
/* Modified by Silas Cutler for Malshare.com  */
/*                                            */
/* ****************************************** */

// error_reporting(E_ALL & ~E_NOTICE);

/* GLOBAL CONFIG VARS */

// Tables
define("SAMPLES_TABLE", "tbl_samples");
define("SAMPLE_SOURCES_TABLE", "tbl_sample_sources");
define("USERS_TABLE", "tbl_users");
define("UPLOADS_TABLE", "tbl_uploads");
define("SEARCHES_TABLE", "tbl_searches");
define("PUBSEARCHES_TABLE", "tbl_public_searches");
define("URLDLTASKS_TABLE", "tbl_url_download_tasks");
define("SAMPLE_PARTNER_TABLE", "tbl_sample_partners");

// External API Connections
define("VT_CONTEXT_KEY", getenv("VT_CONTEXT_KEY"));
define("VT_CONTEXT_URL", getenv("VT_CONTEXT_URL"));

// DB Connection
define("DB_HOST", getenv('MALSHARE_DB_HOST'));
define("DB_USER", getenv('MALSHARE_DB_USER'));
define("DB_PASS", getenv('MALSHARE_DB_PASS'));
define("DB_DATABASE", getenv('MALSHARE_DB_DATABASE'));
define("DB_CA_PATH", getenv('MALSHARE_DB_CERT'));
define("DB_PORT", getenv('MALSHARE_DB_PORT'));

// Supported Hashing
define("HASH_SUPPORTED_MD5", "true");
define("HASH_SUPPORTED_SHA1", "true");
define("HASH_SUPPORTED_SHA256", "true");

// S3 config
define("WASABI_ENDPOINT", getenv('WASABI_ENDPOINT'));
define("WASABI_REGION", getenv('WASABI_REGION'));
define("WASABI_KEY", getenv('WASABI_KEY'));
define("WASABI_SECRET", getenv('WASABI_SECRET'));
define("WASABI_BUCKET", getenv('WASABI_BUCKET'));


class UserObject
{
    public $id;
    public $api_key;
    public $active;
    public $approved;
    public $recursiveUrlDownloadAllowed;
    public $ready;

    function __construct($sql, $submitted_api_key, $web = false)
    {
        $this->ready = false;
        if (! ($stmt = $sql->prepare('SELECT id as id, api_key as api_key, active as active, approved as approved, recursive_url_download_allowed FROM tbl_users WHERE api_key = ? LIMIT 1'))) {
            $row = null;
        } else {
            $stmt->bind_param('s', $submitted_api_key);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_object() : null;
            $stmt->close();
        }
        if ($row === null) {
            $this->id = null;
            $this->api_key = null;
            $this->active = false;
            $this->approved = null;
            $this->recursiveUrlDownloadAllowed = null;
        } else {
            $this->id = $row->id;
            $this->api_key = $row->api_key;
            $this->active = $row->active;
            $this->approved = $row->approved;
            $this->recursiveUrlDownloadAllowed = $row->recursive_url_download_allowed;
            $res->free_result();

            if ($web) {
                if ($this->active == 0) {
                    return false;
                }
                if ($this->approved == 0) {
                    return false;
                }
                $this->ready = true;
            }
            if ($this->active == 0) {
                http_response_code(401);
                usleep(500000);
                die("Error 14000 (Account not activated)");
            }
            if ($this->approved == 0) {
                http_response_code(401);
                usleep(500000);
                die("Error 14001 (Account not approved)");
            }

            $this->ready = true;
        }
    }

}

class ServerObject
{
    public $host_ip;

    public $sample;
    public $filename;

    public $sql;

    public $uri_api_key;
    public $uri_action;
    public $uri_hash;
    public $uri_type;
    public $uri_query;
    public $uri_private;
    public $uri_path;


    // DB Tables
    public $vars_table_samples;
    public $vars_table_users;
    public $vars_table_sources;
    public $vars_table_searches;
    public $vars_table_pub_searches;
    public $vars_table_uploads;
    public $vars_table_url_download_tasks;
    public $vars_table_sample_partners;

    public $vt_context_key;
    public $vt_context_url;

    public $table;

    public $s3Client;

    function __construct()
    {
        $this->s3Client = new Aws\S3\S3Client([
            'credentials' => ['key' => WASABI_KEY, 'secret' => WASABI_SECRET],
            'endpoint' => WASABI_ENDPOINT,
            'region' => WASABI_REGION,
            'version' => 'latest',
            'use_path_style_endpoint' => true,
        ]);
        $this->host_ip = $_SERVER['REMOTE_ADDR'];

        if (defined('DB_CA_PATH')) {
            $this->sql = mysqli_init();
            $this->sql->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
            $this->sql->ssl_set(null, null, DB_CA_PATH, null, null);
            $this->sql->real_connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE, DB_PORT);
            // Ensure connection uses utf8mb4 to match database collation
            // and avoid "Conversion from collation ... impossible for parameter" errors.
            $this->sql->set_charset('utf8mb4');
        } else {
            $this->sql = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
            // Ensure connection uses utf8mb4 to match database collation
            $this->sql->set_charset('utf8mb4');
        }


        if (mysqli_connect_errno()) {
            http_response_code(503);
//            printf("Connect failed: %s\n", mysqli_connect_error());
            $this->error_die("Error 13000 (System Unavailable)");
        }

        if (isset($_COOKIE['mapi_key']) && $_COOKIE['mapi_key'] != "") {
            $this->uri_api_key = $this->secure($_COOKIE['mapi_key']);
        } else {
            $api = $this->getRequestParam('api_key', null);
            $this->uri_api_key = $api !== null ? $this->secure($api) : null;
        }

        $action = $this->getRequestParam('action', null);
        $this->uri_action = $action !== null ? $this->secure($action) : null;

        $hash = $this->getRequestParam('hash', null);
        $this->uri_hash = $hash !== null ? $this->secure(strtolower($hash)) : null;

        $query = $this->getRequestParam('query', null);
        $this->uri_query = $query !== null ? $this->secure(strtolower($query)) : null;

        $private = $this->getRequestParam('private', null);
        $this->uri_private = $private !== null ? $this->secure(strtolower($private)) : null;

        $type = $this->getRequestParam('type', null);
        $this->uri_type = $type !== null ? $this->secure(strtolower($type)) : null;

        $path = $this->getRequestParam('path', null);
        $this->uri_path = $path !== null ? $this->secure($path) : null;

        $filename_hash = $this->getRequestParam('hash', null);
        $this->filename = $filename_hash !== null ? $this->secure(strtolower($filename_hash)) : null;

        if ($this->uri_api_key !== null) {
            if (! preg_match('/^[A-Za-z0-9]+$/', $this->uri_api_key)) {
                http_response_code(400);
                die("No API Key Supplied");
            }
        }

        // Tables
        $this->vars_table_samples = SAMPLES_TABLE;
        $this->vars_table_users = USERS_TABLE;
        $this->vars_table_sources = SAMPLE_SOURCES_TABLE;
        $this->vars_table_searches = SEARCHES_TABLE;
        $this->vars_table_pub_searches = PUBSEARCHES_TABLE;
        $this->vars_table_uploads = UPLOADS_TABLE;
        $this->vars_table_url_download_tasks = URLDLTASKS_TABLE;
        $this->vars_table_sample_partners = SAMPLE_PARTNER_TABLE;

        $this->vt_context_key = VT_CONTEXT_KEY;
        $this->vt_context_url = VT_CONTEXT_URL;
    }

    public function login()
    {
        $uuser = new UserObject($this->sql, $this->uri_api_key, true);

        return $uuser;
    }

    public function secure($string)
    {
        if (! $this->sql) {
            die("ERROR");
        }
        // Ensure we never pass null to strip_tags (PHP 8.1+ deprecation)
        $string = strip_tags((string)$string);
        $string = stripslashes($string);

        // Strip non-ASCII characters to avoid collation conversion issues
        $string = preg_replace('/[^\x00-\x7F]/', '', $string);

        $string = $this->sql->real_escape_string($string);

        return $string;
    }

    public function escape_html($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function getRequestParam($key, $default = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }

        return $default;
    }

    public static function github_issue_url($error_code, $description = '')
    {
        $title = rawurlencode("Error $error_code");
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $body_lines = array("**Error Code:** $error_code");
        if ($description !== '') {
            $body_lines[] = "**Details:** $description";
        }
        if ($url !== '') {
            $body_lines[] = "**URL:** `$url`";
        }
        $body = rawurlencode(implode("\n", $body_lines));
        return "https://github.com/Malshare/MalShare/issues/new?title=$title&body=$body";
    }

    public static function error_message_html($string)
    {
        if (preg_match('/Error\s+(\d+)/', $string, $matches)) {
            $code = $matches[1];
            $issue_url = self::github_issue_url($code, htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8')
                . ' — <a href="' . htmlspecialchars($issue_url, ENT_QUOTES, 'UTF-8') . '">Report this issue</a>';
        }
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public function error_die($string)
    {
        http_response_code(500);
        usleep(500000);
        die(self::error_message_html($string));
    }

    public function redirect($loc)
    {
        if (headers_sent()) {
            echo '<script>window.location.replace(' . json_encode($loc) . ');</script>';
            die();
        }
        http_response_code(302);
        header('Location: ' . $loc);

        return null;
    }

    public function error_die_with_code($code, $string)
    {
        http_response_code($code);
        usleep(500000);
        die(self::error_message_html($string));
    }

    public function load_context($hash)
    {
        $r_hash = $this->secure($hash);
        $hash = preg_replace("/[^a-zA-Z0-9]+/", "", $r_hash);

        $vt_key = $this->vt_context_key;

        $options = array(
            'http' => array(
                'header' => "x-apikey: " . $vt_key . "\r\n",
                'method' => 'GET',
            ),
        );

        $url = $this->vt_context_url . $hash;
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            return false;
        }
        $vt_widget = null;
        if ($result !== null && $result !== '') {
            $vt_widget = json_decode($result);
        }
        $widget = '  <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms" src="' . $vt_widget->{'data'}->{'url'} . '"
          width="100%" height="500" allowfullscreen>
    <p>
      <a href="/en-US/docs/Glossary">
         VT Context:
      </a>
    </p>
  </iframe>';

        return $widget;


    }


    public function get_total()
    {
        $table = $this->vars_table_samples;
        if (! ($stmt = $this->sql->prepare("SELECT count(id) as rcount from $table"))) {
            $this->error_die("Unable to get total sample count ");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            $this->error_die("Unable to get total sample count ");
        }

        $row = $res->fetch_object();
        $stmt->close();

        return $row->rcount;
    }

    public function get_recent()
    {

        $table = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;
        $table_sample_partners = $this->vars_table_sample_partners;;

        $main_stmt = $this->sql->prepare("SELECT id from $table WHERE ( ( pending != 1 or pending is NULL ) AND ftype != 'html' ) ORDER by added DESC limit 10");
        if (! $main_stmt) {
            $this->error_die("Error 13513 (Unable to get recent samples. Please report this issue)");
        }
        $main_stmt->execute();
        $res = $main_stmt->get_result();
        if (! $res) {
            $main_stmt->close();
            $this->error_die("Error 13513 (Unable to get recent samples. Please report this issue)");
        }

        $output = '<table class="table table-bordered table-striped" style="table-layout: fixed;">
        <thead>  <tr>
        <th style="width: 17%;">SHA256 Hash</th>
        <th style="width: 5%">File type</th>
        <th style="width: 13%">Added</th>
        <th style="width: 25%">Source</th>
        <th style="width: 40%">' . h('sample.yara_hits') . '</th>
        </tr>  </thead>  <tbody>';

        while ($s_row = $res->fetch_object()) {
            if ($detail_stmt = $this->sql->prepare("SELECT s.sha256 AS sha256, s.added AS added, s.ftype AS ftype, s.yara AS yara, ts.source AS source, tsp.display_name AS source_display_name FROM {$table} s LEFT JOIN {$table_sources} ts ON s.id = ts.id LEFT JOIN {$table_sample_partners} tsp ON ts.sample_partner_submission = tsp.id WHERE s.id = ?")) {
                $detail_stmt->bind_param('i', $s_row->id);
                $detail_stmt->execute();
                $r_res = $detail_stmt->get_result();
            } else {
                $r_res = false;
            }


            if (! $r_res) {
                $this->error_die(
                    "Error 13512 (Problem getting recent sample details.  Please report this issue)"
                );
            }
            if ($r_res->num_rows == 0) {
                next();
            }

            $sample_row = $r_res->fetch_object();
            if (isset($detail_stmt) && $detail_stmt) {
                $detail_stmt->close();
            }

            $yhits = "";
            $jhits = null;
            $yara_json = $sample_row->yara ?? '';
            if ($yara_json !== null && $yara_json !== '') {
                $jhits = json_decode($yara_json);
            }
            $counter = 0;
            $extend = 0;
            if ($jhits && isset($jhits->yara) && (is_array($jhits->yara) || is_object($jhits->yara))) {
                foreach ($jhits->yara as $yh) {
                    $counter += 1;
                    if ($counter > 3 && $extend == 0) {
                        $yhits .= '<a id="c_yara_' . $sample_row->sha256 . '" class="none" href="#" onclick="document.getElementById(\'yara_' . $sample_row->sha256 . '\').style= \'block\'; document.getElementById(\'c_yara_' . $sample_row->sha256 . '\').className = \'hidden\';">[+]</a>';
                        $yhits .= '<div id="yara_' . $sample_row->sha256 . '" style="display: none;">';


                        $extend = 1;
                    }
                    $yhits .= '<a href="search.php?query=' . rawurlencode($yh) . '"><span class="label label-info">' . $this->escape_html($yh) . '</span></a>  ';
                }

                if ($counter > 3) {
                    $yhits .= "</div>";
                }
            }
            $output .= '<tr>
                    <td class="hash_font"><div style = "word-wrap: break-word"><a href="sample.php?action=detail&hash=' . $sample_row->sha256 . '">' . $sample_row->sha256 . '</a></div></td>
                    <td>' . $sample_row->ftype . '</td>
                    <td>' . date("Y-m-d H:i:s", $sample_row->added) . ' UTC</td>';

            $output .= '<td class="word-wrap: wrap-word">' . $this->sourceForDisplay($sample_row) . '</td> ';
            $output .= '<td>' . $yhits . '</td></tr>';

        }
        $output .= '</tbody></table>';
        $main_stmt->close();

        return $output;
    }

    public function sourceForDisplay($row, $separator = ' | ')
    {
        $source = isset($row->source) ? $row->source : null;
        $source_display = isset($row->source_display_name) ? $row->source_display_name : null;

        if ($source) {
            if ($source_display) {
                return $this->escape_html($source) . $separator . $this->escape_html($source_display);
            }

            return $this->escape_html($source);
        }

        if ($source_display) {
            return $this->escape_html($source_display);
        }

        return $this->escape_html('User Submission');
    }

    public function get_sitemap()
    {
        $output = "";
        $table = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;
        $sql = "SELECT md5, sha1, sha256 FROM $table WHERE ( pending != 1 or pending is NULL ) ORDER by added DESC limit 1000";
        if (! ($stmt = $this->sql->prepare($sql))) {
            $this->error_die("Error 23214 (Problem building sitemap.  Please report this issue)");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            $this->error_die("Error 23214 (Problem building sitemap.  Please report this issue)");
        }

        while ($sample_row = $res->fetch_object()) {
            $output .= '<a href="sample.php?action=detail&hash=' . $sample_row->sha256 . '">' . $sample_row->md5 . ' | ' . $sample_row->sha1 . ' | ' . $sample_row->sha256 . '</a><br />';
        }

        $stmt->close();

        return $output;
    }

    private function getRuleIdByName($ruleName)
    {
        if (! ($stmt = $this->sql->prepare('SELECT id FROM tbl_yara WHERE (lower(rule_name) = lower(?))'))) {
            return null;
        }
        $stmt->bind_param('s', $ruleName);
        $stmt->execute();
        $stmt->bind_result($yaraRuleId);
        $stmt->fetch();

        return $yaraRuleId;
    }

    private function getSampleIdFromHash($hash)
    {
        $r_hash = $this->secure($hash);
        $clean = preg_replace("/[^a-zA-Z0-9]+/", "", $r_hash);

        switch (strlen($clean)) {
            case 32:
                $field = 'md5';
                break;
            case 40:
                $field = 'sha1';
                break;
            case 64:
                $field = 'sha256';
                break;
            default:
                return null;
        }

        $sql = "SELECT id as hash FROM {$this->vars_table_samples} WHERE $field = lower(?) LIMIT 1";
        if (! ($stmt = $this->sql->prepare($sql))) {
            return null;
        }
        $stmt->bind_param('s', $clean);
        $stmt->execute();
        $stmt->bind_result($id);
        if ($stmt->fetch()) {
            return $id;
        }

        return null;
    }



    public function sample_search($api_query = false)
    {
        $table = $this->vars_table_samples;
        $table_samples = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;
        $table_searches = $this->vars_table_searches;
        $table_pub_searches = $this->vars_table_pub_searches;
        $table_sample_partners = $this->vars_table_sample_partners;

        $searchValue = $this->secure($this->uri_query);
        $searchPrivate = 0;
        $source_ip = $this->secure($_SERVER['REMOTE_ADDR']);
        if ($this->secure($this->uri_private) == "on") {
            $searchPrivate = 1;
        }

        if (strlen($searchValue) < 3) {
            $this->error_die("Query must by longer then 3 characters");
        }

        if ($stmt = $this->sql->prepare("INSERT INTO $table_searches (query, source, ts, private) VALUES (?, ?, UNIX_TIMESTAMP(), ?)") ) {
            $stmt->bind_param('ssi', $searchValue, $source_ip, $searchPrivate);
            $stmt->execute();
            $stmt->close();
            $this->sql->commit();
        }

        $searchValueLower = strtolower($searchValue);
        $notAnApiQuery = $api_query == false;

        // If this is a web search and the query is a hash, redirect to details page.
        if ($notAnApiQuery && (strlen($searchValue) == 32 || strlen($searchValue) == 40 || strlen($searchValue) == 64)) {
            return $this->redirect("sample.php?action=detail&hash=" . $searchValue);
        }

        // Run the same selection logic for API and web searches (web hashes already redirected above).
        if (strlen($searchValue) == 32) {
            $stmt = $this->sql->prepare('SELECT id FROM tbl_samples WHERE md5 = ?');
            $stmt->bind_param('s', $searchValue);
            $stmt->execute();
            $res = $stmt->get_result();
        } elseif (strlen($searchValue) == 40) {
            $stmt = $this->sql->prepare('SELECT id FROM tbl_samples WHERE sha1 = ?');
            $stmt->bind_param('s', $searchValue);
            $stmt->execute();
            $res = $stmt->get_result();
        } elseif (strlen($searchValue) == 64) {
            $stmt = $this->sql->prepare('SELECT id FROM tbl_samples WHERE sha256 = ?');
            $stmt->bind_param('s', $searchValue);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            if (substr($searchValue, 0, 7) == "source:") {
                $rhash = trim(explode(":", $searchValue)[1]);
                $tokens = preg_split('/[^a-zA-Z0-9]+/', $rhash, -1, PREG_SPLIT_NO_EMPTY);

                if (!empty($tokens)) {
                    $ftQuery = '';
                    foreach ($tokens as $token) {
                        $ftQuery .= '+' . $token . '* ';
                    }
                    $ftQuery = trim($ftQuery);

                    $stmt = $this->sql->prepare("SELECT DISTINCT id FROM $table_sources WHERE MATCH(source) AGAINST(? IN BOOLEAN MODE) LIMIT 100");
                    $stmt->bind_param('s', $ftQuery);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    // Fallback to LIKE if FULLTEXT returns nothing (short tokens, substring patterns)
                    if ($res && $res->num_rows === 0) {
                        $like = "%" . $rhash . "%";
                        $stmt = $this->sql->prepare("SELECT DISTINCT id FROM $table_sources WHERE source LIKE ? LIMIT 100");
                        $stmt->bind_param('s', $like);
                        $stmt->execute();
                        $res = $stmt->get_result();
                    }
                } else {
                    $like = "%" . $rhash . "%";
                    $stmt = $this->sql->prepare("SELECT DISTINCT id FROM $table_sources WHERE source LIKE ? LIMIT 100");
                    $stmt->bind_param('s', $like);
                    $stmt->execute();
                    $res = $stmt->get_result();
                }
            } else if (substr($searchValue, 0, 5) == "type:") {
                $ftype = strtoupper(trim(substr($searchValue, 5)));
                $stmt = $this->sql->prepare("SELECT id FROM $table WHERE ftype = ? ORDER BY added DESC LIMIT 100");
                $stmt->bind_param('s', $ftype);
                $stmt->execute();
                $res = $stmt->get_result();

                // Fallback: case-insensitive scan for mixed-case file types
                if ($res && $res->num_rows === 0) {
                    $ftype_lower = strtolower($ftype);
                    $stmt = $this->sql->prepare("SELECT id FROM $table WHERE LOWER(ftype) = ? ORDER BY added DESC LIMIT 100");
                    $stmt->bind_param('s', $ftype_lower);
                    $stmt->execute();
                    $res = $stmt->get_result();
                }
            } else {
                if (substr($searchValue, 0, 4) == "yrp/") { // startswith
                    $yaraId = $this->getRuleIdByName(substr($searchValue, 4));
                    if (! $yaraId) {
                        return '<p>YARA rule with this name could not be found</p>';
                    }
                    $stmt = $this->sql->prepare('SELECT s.id FROM tbl_samples s LEFT JOIN tbl_matches m ON (s.id = m.sample_id) WHERE (m.yara_id = ?) ORDER BY s.added DESC LIMIT 100');
                    $stmt->bind_param('i', $yaraId);
                    $stmt->execute();
                    $res = $stmt->get_result();
                } else {
                    $searchValueLower = strtolower($searchValue);
                    $like = $searchValueLower . '%';
                    $stmt = $this->sql->prepare("SELECT id FROM tbl_sample_sources WHERE source LIKE ? LIMIT 100");
                    $stmt->bind_param('s', $like);
                    $stmt->execute();
                    $res = $stmt->get_result();
                }
            }
        }

        if (! $res) {
            $this->error_die("Error 13843 (System error while searching.  Please report this issue)");
        }

        // Build header / if not API
        if ($notAnApiQuery) {
            $output = '<table class="table table-bordered table-striped" style="table-layout: fixed;">
        <thead>  <tr>
        <th style="width: 17%;">SHA256 Hash</th>
        <th style="width: 5%">File type</th>
        <th style="width: 13%">Added</th>
        <th style="width: 25%">Source</th>
        <th style="width: 40%">' . h('sample.yara_hits') . '</th>
        </tr>  </thead>  <tbody>';
        } else {
            header('Content-Type: application/json');
            $output = array();
        }
        // Fetch data
        $totalHits = 0;
        while ($s_row = $res->fetch_object()) {
            if (! ($stmt = $this->sql->prepare(
                "SELECT
                       s.id AS id,
                       s.md5 AS md5,
                       s.sha1 AS sha1,
                       s.sha256 AS sha256,
                       s.added AS added,
                       s.ftype AS ftype,
                       s.yara AS yara,
                       ts.source AS source,
                       tsp.display_name AS display_name_source,
                       s.parent_id
                FROM {$table} s
                    LEFT JOIN {$table_sources} ts ON s.id = ts.id
                    LEFT JOIN {$table_sample_partners} tsp ON ts.sample_partner_submission = tsp.id
                WHERE s.id = ?"
            ))) {
                $this->error_die("Error 13842 (Problem fetching search results.  Please report this issue)");
            }
            $stmt->bind_param('i', $s_row->id);
            $stmt->execute();
            $r_res = $stmt->get_result();
            if (! $r_res) {
                $stmt->close();
                $this->error_die("Error 13842 (Problem fetching search results.  Please report this issue)");
            }
            if ($r_res->num_rows == 0) {
                $stmt->close();
                next();
            }

            $sample_row = $r_res->fetch_object();
            $stmt->close();
            $totalHits += 1;
            $source = $this->sourceForDisplay($sample_row, '<br/>');

            // if not an API query, build HTML
            if ($notAnApiQuery) {
                $output .= '<tr>
                    <td class="hash_font"><div style = "word-wrap: break-word"><a href="sample.php?action=detail&hash=' . $sample_row->sha256 . '">' . $sample_row->sha256 . '</a></div></td>
                    <td>' . $sample_row->ftype . '</td>
                    <td>' . date("Y-m-d H:i:s", $sample_row->added) . '</td>';

                if (strlen($source) > 45) {
                    $output .= '<td>' . substr($source, 0, 45) . '...</td> ';
                } else {
                    $output .= '<td>' . $source . '</td> ';
                }

                $yhits = "";
                $jhits = null;
                $yara_json = $sample_row->yara ?? '';
                $yarahits_decoded = null;
                if ($yara_json !== null && $yara_json !== '') {
                    $jhits = json_decode($yara_json);
                    $yarahits_decoded = $jhits;
                }

                if ($jhits && isset($jhits->yara) && (is_array($jhits->yara) || is_object($jhits->yara))) {
                    $extend = 0;
                    $counter = 0;
                    foreach ($jhits->yara as $yh) {
                        $counter += 1;
                        if ($counter > 4 && $extend == 0) {

                            $yhits .= '<a id="c_yara_' . $sample_row->sha256 . '" class="none" href="#" onclick="document.getElementById(\'yara_' . $sample_row->sha256 . '\').style= \'block\'; document.getElementById(\'c_yara_' . $sample_row->sha256 . '\').className = \'hidden\';">[+]</a>';
                            $yhits .= '<div id="yara_' . $sample_row->sha256 . '" style="display: none;">';

                            $extend = 1;
                        }
                        $yhits .= '<a href="search.php?query=' . rawurlencode($yh) . '"><span class="label label-info">' . $this->escape_html($yh) . '</span></a>  ';
                    }
                    if ($counter > 4) {
                        $yhits .= "</div>";
                    }
                }
                $output .= '<td>' . $yhits . '</td></tr>';
                $output .= '</tr>';
            } else {
                $t = array(
                    //                    'id' => $sample_row->id,
                    //                    'parentid' => $sample_row->parent_id,
                    'md5' => $sample_row->md5,
                    'sha1' => $sample_row->sha1,
                    'sha256' => $sample_row->sha256,
                    'type' => $sample_row->ftype,
                    'added' => intval($sample_row->added),
                    'source' => $source,
                    'yarahits' => $yarahits_decoded,
                    'parentfiles' => array(),
                    'subfiles' => array(),
                );

                if (($sample_row->parent_id != null)) {
                    if (strpos($sample_row->parent_id, ',') !== false) {
                        $parent_ids = explode(",", $sample_row->parent_id);
                    } else {
                        $parent_ids = array($sample_row->parent_id);
                    }

                    foreach ($parent_ids as $pid) {
                        if (! ($pstmt = $this->sql->prepare("SELECT md5, sha1, sha256 FROM $table WHERE id = ?"))) {
                            $this->error_die("Error 138413 (Problem getting sample parents. Please report this issue)");
                        }
                        $pstmt->bind_param('i', $pid);
                        $pstmt->execute();
                        $full_res = $pstmt->get_result();
                        if (! $full_res) {
                            $pstmt->close();
                            $this->error_die("Error 138413 (Problem getting sample parents. Please report this issue)");
                        }
                        if ($full_res->num_rows > 0) {
                            while ($s_row = $full_res->fetch_object()) {
                                array_push(
                                    $t['parentfiles'],
                                    array('md5' => $s_row->md5, 'sha1' => $s_row->sha1, 'sha256' => $s_row->sha256)
                                );
                            }
                        }
                        $pstmt->close();
                    }
                }

                if (! ($cstmt = $this->sql->prepare("SELECT md5, sha1, sha256 FROM $table WHERE parent_id = ?"))) {
                    $this->error_die("Error 13849 (Problem getting child files. Please report this issue)");
                }
                $cstmt->bind_param('i', $sample_row->id);
                $cstmt->execute();
                $full_res = $cstmt->get_result();
                if (! $full_res) {
                    $cstmt->close();
                    $this->error_die("Error 13849 (Problem getting child files. Please report this issue)");
                }
                if ($full_res->num_rows > 0) {
                    while ($s_row = $full_res->fetch_object()) {
                        array_push(
                            $t['subfiles'],
                            array('md5' => $s_row->md5, 'sha1' => $s_row->sha1, 'sha256' => $s_row->sha256)
                        );
                    }
                }
                $cstmt->close();

                #$output .= json_encode($t, JSON_UNESCAPED_SLASHES);
                array_push($output, $t);
            }
        }

        if (($api_query == false) && ($totalHits > 0) && ($searchPrivate == 0)) {
            if ($stmt = $this->sql->prepare("INSERT INTO $table_pub_searches (query, ts) VALUES (?, UNIX_TIMESTAMP())")) {
                $stmt->bind_param('s', $searchValue);
                $stmt->execute();
                $stmt->close();
                $this->sql->commit();
            }
        }

        if ($notAnApiQuery) {
            $output .= '</tbody></table>  ';

            return $output;
        } else {
            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
    }

    public function get_details()
    {
        $r_hash = $this->uri_hash;
        $hash = preg_replace("/[^a-zA-Z0-9]+/", "", $r_hash);

        $table = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;
        $table_sample_partners = $this->vars_table_sample_partners;
        $table_uploads = $this->vars_table_uploads;


        $id = $this->getSampleIdFromHash($hash);
        if ($id === null) {
            http_response_code(404);
            usleep(500000);
            die("Sample not found with hash ( $hash )");
        }

        $row = (object)['hash' => $id];

        if (! ($stmt = $this->sql->prepare("SELECT md5, sha1, sha256, ssdeep, added, ftype, yara, pending, parent_id FROM $table WHERE id = ?"))) {
            $this->error_die("Error 23418 (Unable to find child samples  Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (! $full_res || $full_res->num_rows == 0) {
            $stmt->close();
            http_response_code(404);
            usleep(500000);
            die("Error Sample not found by hash ($hash)");
        }
        $f_row = $full_res->fetch_object();
        $stmt->close();

        $dt = new DateTime("@$f_row->added");

        $output = '<br />
            <button type"submit">
            <a href="sampleshare.php?action=getfile&hash=' . $f_row->sha256 . '">Download</a></button>

            <table class="table">
            <thead>
              <tr>
                <th>Hashes</th>

              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="hash_font"><b>MD5</b>:   ' . $f_row->md5 . '</td>
              </tr>
              <tr>
                <td class="hash_font"><b>SHA1</b>:   ' . $f_row->sha1 . '</td>
              </tr>
              <tr>
                <td class="hash_font"><b>SHA256</b>:   ' . $f_row->sha256 . '</td>
              </tr>
              <tr>
                <td class="hash_font"><b>SSDEEP</b>:   ' . $f_row->ssdeep . '</td>
              </tr>
              <tr>
                <td class="hash_font"><b>File Type</b>:   ' . $this->escape_html($f_row->ftype) . '</td>
              </tr>
              <tr>
                <td class="hash_font"><b>Added</b>:   ' . date("Y-m-d H:i:s", $f_row->added) . '</td>
              </tr>
            </tbody>
            </table>
        ';
        if (! ($fname_stmt = $this->sql->prepare("SELECT DISTINCT name FROM $table_uploads WHERE md5 = ?"))) {
            $this->error_die("Error 23428 (Unable to find file names  Please report this issue)");
        }
        $fname_stmt->bind_param('s', $f_row->md5);
        $fname_stmt->execute();
        $fname_search = $fname_stmt->get_result();
        if (! $fname_search) {
            $fname_stmt->close();
            $this->error_die("Error 23428 (Unable to find file names  Please report this issue)");
        }
        $hash_values = array_map('strtolower', [$f_row->md5, $f_row->sha1, $f_row->sha256]);
        $filtered_names = [];
        while ($trow = $fname_search->fetch_object()) {
            if (!in_array(strtolower(trim($trow->name)), $hash_values, true)) {
                $filtered_names[] = $trow->name;
            }
        }
        if (count($filtered_names) > 0) {
            $output .= '<table class="table"><thead><tr><th>' . h('sample.observed_file_names') . '</th></tr></thead><tbody>';
            foreach ($filtered_names as $fname) {
                $output .= '<tr><td>' . $this->escape_html($fname) . '</td> </tr>';
            }
            $output .= '</tbody></table>';
        }
        $fname_stmt->close();
        $jhits = null;
        $yara_json = $f_row->yara ?? '';
        if ($yara_json !== null && $yara_json !== '') {
            $jhits = json_decode($yara_json);
        }
        if ($jhits && isset($jhits->yara) && (is_array($jhits->yara) || is_object($jhits->yara)) && count((array)$jhits->yara) > 0) {
            $output .= '<table class="table"><thead><tr><th>' . h('sample.yara_hits') . '</th></tr></thead><tbody><tr><td>';
            foreach ($jhits->yara as $yh) {
                $output .= '<span class="label label-info">' . $yh . '</span> | ';
            }
            $output .= " </td></tr>
        </tbody>
        </table>";
        }

        if ($f_row->parent_id != null and $f_row->parent_id != -1) {
            $output .= '
                <table class="table">
                <thead>
                        <tr>
                                <th>Parent Files</th>
                        </tr>
                </thead>
                <tbody>
            ';

            if (strpos($f_row->parent_id, ',') !== false) {
                $parent_ids = explode(",", $f_row->parent_id);
            } else {
                $parent_ids = array($f_row->parent_id);
            }

            foreach ($parent_ids as $pid) {
                if (! ($pstmt = $this->sql->prepare("SELECT sha256 FROM $table WHERE id = ?"))) {
                    $this->error_die("Error 23732 (Problem finding parent details for hash.  Please report this issue)");
                }
                $pstmt->bind_param('i', $pid);
                $pstmt->execute();
                $full_res = $pstmt->get_result();
                if (! $full_res) {
                    $pstmt->close();
                    $this->error_die("Error 23732 (Problem finding parent details for hash.  Please report this issue)");
                }
                if ($full_res->num_rows > 0) {
                    while ($s_row = $full_res->fetch_object()) {
                        $output .= '<tr> <td><a href="sample.php?action=detail&hash=' . $s_row->sha256 . '">' . $s_row->sha256 . '</a></td> </tr>';
                    }
                    $output .= '
                                    </tbody>
                            </table>
                    ';
                }
                $pstmt->close();
            }
        }
        if (! ($cstmt = $this->sql->prepare("SELECT sha256 FROM $table WHERE parent_id = ?"))) {
            $this->error_die("Error 23734 (Problem finding child samples.  Please report this issue)");
        }
        $cstmt->bind_param('i', $row->hash);
        $cstmt->execute();
        $full_res = $cstmt->get_result();
        if (! $full_res) {
            $cstmt->close();
            $this->error_die("Error 23734 (Problem finding child samples.  Please report this issue)");
        }
        if ($full_res->num_rows > 0) {
            $output .= '
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sub Files</th>
                        </tr>
                    </thead>
                    <tbody>
            ';
            while ($s_row = $full_res->fetch_object()) {
            $output .= '<tr> <td><a href="sample.php?action=detail&hash=' . $s_row->sha256 . '">' . $s_row->sha256 . '</a></td> </tr>';
            }
            $output .= '
                    </tbody>
                </table>
            ';
        }
        $cstmt->close();

        if (! ($stmt = $this->sql->prepare(
            "SELECT ts.source AS source, tsp.display_name AS source_display_name FROM $table_sources ts LEFT JOIN $table_sample_partners tsp ON ts.sample_partner_submission = tsp.id WHERE ts.id = ?"
        ))) {
            $this->error_die("Error 23735 (Problem finding sources for sample.  Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (! $full_res) {
            $stmt->close();
            $this->error_die("Error 23735 (Problem finding sources for sample.  Please report this issue)");
        }
        if ($full_res->num_rows > 0) {
            $output .= '
                <table class="table">
                    <thead>
                        <tr>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                ';
            while ($s_row = $full_res->fetch_object()) {
                $output .= '<tr><td>' . $this->sourceForDisplay($s_row) . '</td></tr>';
            }
            $output .= '</tbody></table>';
        }
        $stmt->close();

// VT Context:
        $vt_context = $this->load_context($hash);
        if ($vt_context != false) {
            $output .= '<table class="table"><thead><tr><th>VT Context</th></tr></thead><tbody><tr><td>';
            $output .= $vt_context;
            $output .= " </td></tr></tbody></table>";

        }
        if ($f_row->pending == 1) {
            $output .= "<script>ShowLoading();</script>";
        }

        return $output;

    }

    public function get_details_json()
    {
        header('Content-Type: application/json');
        $output = array();

        $r_hash = $this->uri_hash;
        $hash = preg_replace("/[^a-zA-Z0-9]+/", "", $r_hash);

        $table = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;
        $table_uploads = $this->vars_table_uploads;

        $id = $this->getSampleIdFromHash($hash);
        if ($id === null) {
            http_response_code(404);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 404;
            $output['ERROR']["MESSAGE"] = "Sample not found";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $row = (object)['hash' => $id];

        if (! ($stmt = $this->sql->prepare("SELECT md5, sha1, sha256, ssdeep, added, ftype FROM $table WHERE id = ?"))) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724341;
            $output['ERROR']["MESSAGE"] = "problem getting details for hash (json).  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (! $full_res || $full_res->num_rows == 0) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 500;
            $output['ERROR']["MESSAGE"] = "Sample details not found";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        $f_row = $full_res->fetch_object();
        $stmt->close();
        $output['MD5'] = $f_row->md5;
        $output['SHA1'] = $f_row->sha1;
        $output['SHA256'] = $f_row->sha256;
        $output['SSDEEP'] = $f_row->ssdeep;
        $output['F_TYPE'] = $f_row->ftype;

        if (! ($stmt = $this->sql->prepare("SELECT source FROM $table_sources WHERE id = ?"))) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724323;
            $output['ERROR']["MESSAGE"] = "Problem getting sources for hash.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (! $full_res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724323;
            $output['ERROR']["MESSAGE"] = "Problem getting sources for hash.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $t_source = array();
        while ($s_row = $full_res->fetch_object()) {
            array_push($t_source, $s_row->source);
        }
        $stmt->close();

        if (! ($nstmt = $this->sql->prepare("SELECT name FROM $table_uploads WHERE md5 = ?"))) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724323;
            $output['ERROR']["MESSAGE"] = "Problem getting sources for hash.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $nstmt->bind_param('s', $f_row->md5);
        $nstmt->execute();
        $name_res = $nstmt->get_result();
        if (! $name_res) {
            $nstmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724323;
            $output['ERROR']["MESSAGE"] = "Problem getting sources for hash.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $t_names = array();
        while ($f_row = $name_res->fetch_object()) {
            array_push($t_names, $f_row->name);
        }
        $nstmt->close();

        $output['FILENAMES'] = $t_names;

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function get_hashes(array $hashes)
    {
        $sha256s = [];
        $sha1s = [];
        $md5s = [];
        foreach ($hashes as &$hash) {
            $hash = trim(strtolower($hash));
            if (preg_match('/^[a-f0-9]{32}$/', $hash)) {
                $md5s[] = $hash;
            } elseif (preg_match('/^[a-f0-9]{40}$/', $hash)) {
                $sha1s[] = $hash;
            } elseif (preg_match('/^[a-f0-9]{64}$/', $hash)) {
                $sha256s[] = $hash;
            }
        }
        $where = [];
        if ($md5s) {
            $where[] = '(md5 IN ("' . implode('", "', $md5s) . '"))';
        }
        if ($sha1s) {
            $where[] = '(sha1 IN ("' . implode('", "', $sha1s) . '"))';
        }
        if ($sha256s) {
            $where[] = '(sha256 IN ("' . implode('", "', $sha256s) . '"))';
        }
        if (! $where) {
            return [];
        }
        $clauses = [];
        $params = [];
        $types = '';

        if ($md5s) {
            $placeholders = implode(',', array_fill(0, count($md5s), '?'));
            $clauses[] = '(md5 IN (' . $placeholders . '))';
            foreach ($md5s as $m) {
                $params[] = $m;
                $types .= 's';
            }
        }
        if ($sha1s) {
            $placeholders = implode(',', array_fill(0, count($sha1s), '?'));
            $clauses[] = '(sha1 IN (' . $placeholders . '))';
            foreach ($sha1s as $s) {
                $params[] = $s;
                $types .= 's';
            }
        }
        if ($sha256s) {
            $placeholders = implode(',', array_fill(0, count($sha256s), '?'));
            $clauses[] = '(sha256 IN (' . $placeholders . '))';
            foreach ($sha256s as $s2) {
                $params[] = $s2;
                $types .= 's';
            }
        }

        if (! $clauses) {
            return [];
        }

        $sql = 'SELECT sha256, md5, sha1 FROM ' . $this->vars_table_samples . ' WHERE (' . implode(' OR ', $clauses) . ')';
        if (! ($stmt = $this->sql->prepare($sql))) {
            return [];
        }

        if ($params) {
            // bind_param requires references
            $bind_names = [];
            $bind_names[] = &$types;
            for ($i = 0; $i < count($params); $i++) {
                $bind_names[] = &$params[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $stmt->execute();
        $stmt->bind_result($sha256, $md5, $sha1);
        $ret = [];
        while ($stmt->fetch()) {
            $ret[] = [
                'sha256' => $sha256,
                'md5' => $md5,
                'sha1' => $sha1,
            ];
        }
        $stmt->close();

        return $ret;
    }

    private function sample_key($hash)
    {
        $part1 = substr($hash, 0, 3);
        $part2 = substr($hash, 3, 3);
        $part3 = substr($hash, 6, 3);

        return "$part1/$part2/$part3/$hash";
    }

    public function get_sample_url($hash)
    {
        if ($hash == "") {
            $this->error_die("Empty hash specified");
        }

        switch (strlen($hash)) {
            case 32:
                $searchFieldName = 'md5';
                break;
            case 40:
                $searchFieldName = 'sha1';
                break;
            case 64:
                $searchFieldName = 'sha256';
                break;
            default:
                http_response_code(404);
                usleep(500000);
                die("Invalid Hash...");
        }
        $clean = preg_replace('/[^a-f0-9]/', '', strtolower($hash));
        if (! ($stmt = $this->sql->prepare("SELECT sha256 AS hash, md5 AS md5 FROM {$this->vars_table_samples} WHERE $searchFieldName = lower(?)"))) {
            $this->error_die("Error 13940 (Problem finding sample. Please report this issue)");
        }
        $stmt->bind_param('s', $clean);
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res || $res->num_rows == 0) {
            $stmt->close();
            http_response_code(404);
            usleep(500000);
            die("Sample not found by hash ($hash)");
        }
        $row = $res->fetch_object();
        $stmt->close();
        if ($row->hash == "" || $row->md5 == "") {
            http_response_code(404);
            usleep(500000);
            die("Sample not found by hash ($hash)");
        }
        if (! $row->hash) {
            http_response_code(400);
            die("No hash specified in `hash` GET variable");
        }

        $s3Key = $this->sample_key($row->hash);

        if (! $this->s3Client->doesObjectExist(WASABI_BUCKET, $s3Key)) {
            http_response_code(404);
            $this->error_die_with_code(404, "Error 12413 (Sample Missing. Please report this issue)");
        }
        $cmd = $this->s3Client->getCommand('GetObject', ['Bucket' => WASABI_BUCKET, 'Key' => $s3Key]);
        $request = $this->s3Client->createPresignedRequest($cmd, '+5 minutes');

        return (string)$request->getUri();
    }

    public function send_headers($filename)
    {
        header("Pragma: public\n");
        header("Content-Type: application/octet-stream\n");
        header("Content-Disposition: attachment; filename=$filename\n");
        header("Content-transfer-encoding: binary\n");
    }

    public function get_list()
    {
        header('Content-Type: application/json');
        $output = array();

        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT md5 as md5, sha1 as sha1, sha256 as sha256 FROM $table WHERE ( added > ( UNIX_TIMESTAMP() - 86400) )");
        if (! $stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 131312;
            $output['ERROR']["MESSAGE"] = "Unable to generate sample list.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 131312;
            $output['ERROR']["MESSAGE"] = "Unable to generate sample list.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        while ($row = $res->fetch_object()) {
            array_push($output, array('md5' => $row->md5, 'sha1' => $row->sha1, 'sha256' => $row->sha256));
        }
        $stmt->close();

        return json_encode($output);
    }

    public function get_list_raw()
    {
        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT md5 as md5, sha1 as sha1, sha256 as sha256 FROM $table WHERE ( added > ( UNIX_TIMESTAMP() - 86400) )");
        if (! $stmt) {
            $this->error_die("Error 131311 (Please report this issue)");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            $this->error_die("Error 131311 (Please report this issue)");
        }

        while ($row = $res->fetch_object()) {
            print("$row->md5 $row->sha1 $row->sha256\n");
        }
        $stmt->close();
    }

    public function sample_details_raw($hash)
    {
        $output = array();

        $table = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;

        $id = $this->getSampleIdFromHash($hash);
        if ($id === null) {
            http_response_code(404);
            $this->error_die("Invalid Hash.");
        }
        $row = (object)['hash' => $id];


        if (! ($stmt = $this->sql->prepare("SELECT md5, sha1, sha256, ssdeep, added, ftype FROM $table WHERE id = ?"))) {
            $this->error_die("Error 139432 (Problem getting sample details. Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (! $full_res || $full_res->num_rows == 0) {
            $stmt->close();
            http_response_code(404);
            usleep(500000);
            die("Sample not found by hash ($hash)");
        }
        $f_row = $full_res->fetch_object();
        $stmt->close();
        $output['MD5'] = $f_row->md5;
        $output['SHA1'] = $f_row->sha1;
        $output['SHA256'] = $f_row->sha256;
        $output['SSDEEP'] = $f_row->ssdeep;
        $output['F_TYPE'] = $f_row->ftype;
        $output['ADDED'] = $f_row->added;

        if (! ($stmt = $this->sql->prepare("SELECT source FROM $table_sources WHERE id = ?"))) {
            $this->error_die("Error 139312 (Problem sample sources. Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (! $full_res || $full_res->num_rows == 0) {
            $stmt->close();
            http_response_code(404);
            usleep(500000);
            die("Sample not found by hash ($hash)");
        }
        $t_source = array();
        while ($s_row = $full_res->fetch_object()) {
            array_push($t_source, $s_row->source);
        }
        $stmt->close();
        $output['SOURCES'] = $t_source;

        return $output;
    }


    public function get_sum()
    {
        header('Content-Type: application/json');

        $output = array();
        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT sha256 as sha256 FROM $table WHERE ( added > ( UNIX_TIMESTAMP() - 86400) )");
        if (! $stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 139001;
            $output['ERROR']["MESSAGE"] = "Problem pulling sample count.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 139001;
            $output['ERROR']["MESSAGE"] = "Problem pulling sample count.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        while ($row = $res->fetch_object()) {
            array_push($output, $this->sample_details_raw($row->sha256));
        }
        $stmt->close();

        return json_encode($output);
    }

    public function search_type_day()
    {
        header('Content-Type: application/json');

        $results = array();

        $table = $this->vars_table_samples;

        $r_type = $this->uri_type;

        $type = preg_replace("/[^a-zA-Z0-9]+/", "", $r_type);

        $stmt = $this->sql->prepare("SELECT md5 as md5, sha1 as sha1, sha256 as sha256 FROM $table WHERE ( added > ( UNIX_TIMESTAMP() - 86400) and lower(ftype) = ? )");
        if (! $stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 131132;
            $output['ERROR']["MESSAGE"] = "Problem pulling results for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 131132;
            $output['ERROR']["MESSAGE"] = "Problem pulling results for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        while ($row = $res->fetch_object()) {
            array_push($results, array('md5' => $row->md5, 'sha1' => $row->sha1, 'sha256' => $row->sha256));
        }

        return json_encode($results);
    }

    public function get_types()
    {
        header('Content-Type: application/json');

        $output = array();
        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT ftype as ftype, count(id) as fcount from $table WHERE added > (unix_timestamp() - 86400) AND ftype != '-' GROUP BY ftype");
        if (! $stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138523;
            $output['ERROR']["MESSAGE"] = "Problem pulling types from the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138523;
            $output['ERROR']["MESSAGE"] = "Problem pulling types from the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        while ($row = $res->fetch_object()) {
            $output[$row->ftype] = intval($row->fcount);
        }
        $stmt->close();

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function get_filenames()
    {
        header('Content-Type: application/json');

        $output = array();
        $table = $this->vars_table_uploads;
        $stmt = $this->sql->prepare("SELECT distinct name as name FROM $table WHERE ( ts > ( UNIX_TIMESTAMP()-86400) and ts is not NULL )");
        if (! $stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138043;
            $output['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138043;
            $output['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        while ($row = $res->fetch_object()) {
            array_push($output, $row->name);
        }
        $stmt->close();

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function get_sources()
    {
        header('Content-Type: application/json');

        $output = array();
        $table = $this->vars_table_sources;
        $stmt = $this->sql->prepare("SELECT distinct source as source FROM $table WHERE ( added > ( UNIX_TIMESTAMP()-86400) and added is not NULL )");
        if (! $stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138023;
            $output['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138023;
            $output['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }

        while ($row = $res->fetch_object()) {
            array_push($output, $row->source);
        }
        $stmt->close();

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function get_sources_raw()
    {
        $table = $this->vars_table_sources;
        $stmt = $this->sql->prepare("SELECT distinct source as source FROM $table WHERE ( added > ( UNIX_TIMESTAMP()-86400) and added is not NULL )");
        if (! $stmt) {
            $this->error_die(
                "Error 138024. (Problem pulling raw source list for the past day. Please report this issue)"
            );
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            $this->error_die(
                "Error 138024. (Problem pulling raw source list for the past day. Please report this issue)"
            );
        }

        while ($row = $res->fetch_object()) {
            print("$row->source\n");
        }
        $stmt->close();
    }

    public function terminate_api_key()
    {
        header('Content-Type: application/json');

        $table = $this->vars_table_users;
        $api_key =  $this->secure($this->uri_api_key);


        if (! ($stmt = $this->sql->prepare("UPDATE $table SET active = 0 WHERE api_key = ?"))) {
            $this->error_die("Error 432999 (Please report this issue)");
        }
        $stmt->bind_param('s', $api_key);
        if (! $stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 432999 (Please report this issue)");
        }
        $stmt->close();

        $output["message"] = "Thank you";

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function get_user_limit()
    {
        header('Content-Type: application/json');
        $output = array();
        $table = $this->vars_table_users;
        $api_key = $this->uri_api_key;
        if (! ($stmt = $this->sql->prepare("SELECT query_limit, query_base FROM $table WHERE api_key = ?"))) {
            http_response_code(500);
            $eoutput = array();
            $eoutput['ERROR'] = array();
            $eoutput['ERROR']["CODE"] = 439021;
            $eoutput['ERROR']["MESSAGE"] = "Unable to fetch limits.  Please report this issue";

            return json_encode($eoutput, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('s', $api_key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_object() : null;
        if ($row === null) {
            return null;
        }

        try {
            $output["LIMIT"] = $row->query_base;
            $output["REMAINING"] = $row->query_limit;
        } catch (Exception $user_limit_exception) {
            http_response_code(500);
            $eoutput = array();
            $eoutput['ERROR'] = array();
            $eoutput['ERROR']["CODE"] = 439022;
            $eoutput['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($eoutput, JSON_UNESCAPED_SLASHES);
        }

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function update_query_limit()
    {
        $table = $this->vars_table_users;
        $api_key = $this->uri_api_key;
        if (! ($stmt = $this->sql->prepare("SELECT query_limit, last_query FROM $table WHERE api_key = ?"))) {
            $this->error_die("Error 432101 (Please report this issue)");
        }
        $stmt->bind_param('s', $api_key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_object() : null;

        if (! $row) {
            $this->error_die("Error 432101 (Please report this issue)");
        }

        if ($row->query_limit <= 0) {
            if (($row->last_query + 86400) < time()) {
                if (! ($u = $this->sql->prepare("UPDATE $table SET query_limit = query_base - 1 WHERE api_key = ?"))) {
                    $this->error_die("Error 432103 (Please report this issue)");
                }
                $u->bind_param('s', $api_key);
                if (! $u->execute()) {
                    $u->close();
                    $this->error_die("Error 432103 (Please report this issue)");
                }
                $u->close();
            } else {
                http_response_code(429);
                sleep(5);
                die(self::error_message_html("Error 4290 (Over Request Limit)"));
            }
        } else {
            if (! ($u = $this->sql->prepare("UPDATE $table SET query_limit = query_limit - 1, last_query = UNIX_TIMESTAMP() WHERE api_key = ?"))) {
                $this->error_die("Error 492104 (Please report this issue)");
            }
            $u->bind_param('s', $api_key);
            if (! $u->execute()) {
                $u->close();
                $this->error_die("Error 492104 (Please report this issue)");
            }
            $u->close();
        }
    }

    public function increment_query_limit()
    {
        $table = $this->vars_table_users;
        $api_key = $this->uri_api_key;

        if (! ($stmt = $this->sql->prepare("UPDATE $table SET query_limit = query_limit + 1 WHERE api_key = ?"))) {
            $this->error_die("Error 432114 (Please report this issue)");
        }
        $stmt->bind_param('s', $api_key);
        if (! $stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 432114 (Please report this issue)");
        }
        $stmt->close();
    }


    public function update_sample_count($hash)
    {
        $table = $this->vars_table_samples;
        if (! ($stmt = $this->sql->prepare("UPDATE $table SET counter = counter + 1 WHERE md5 = ?"))) {
            $this->error_die("Error 432201 (Please report this issue)");
        }
        $stmt->bind_param('s', $hash);
        if (! $stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 432201 (Please report this issue)");
        }
        $stmt->close();
    }

    public function mark_processing($hash)
    {
        $table = $this->vars_table_samples;
        if (! ($stmt = $this->sql->prepare("UPDATE $table SET processed = 1 WHERE md5 = ?"))) {
            $this->error_die("Error 630001 (Please report this issue)");
        }
        $stmt->bind_param('s', $hash);
        if (! $stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 630001 (Please report this issue)");
        }
        $stmt->close();
    }

    public function get_next_unprocessed()
    {
        $table = $this->vars_table_samples;
        if (! ($stmt = $this->sql->prepare("SELECT md5 as hash FROM $table where processed = 0 order by added limit 1"))) {
            $this->error_die("Error 630002 (Please report this issue)");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            $this->error_die("Error 630002 (Please report this issue)");
        }
        if ($res->num_rows == 0) {
            $stmt->close();
            $this->error_die("Error 63003 No samples waiting processing.");
        }

        $row = $res->fetch_object();
        $stmt->close();

        return $row->hash;
    }

    public function stats_get_types()
    {
        $results = array();

        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT ftype as ftype, count(id) as fcount from $table WHERE added > (unix_timestamp() - 86400) AND ftype != '-' GROUP BY ftype limit 8");
        if (! $stmt) {
            return "Error 132522 (Unable to list file types.  Please report this issue)";
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            return "Error 132522 (Unable to list file types.  Please report this issue)";
        }
        if ($res->num_rows == 0) {
            $stmt->close();
            return "Error 132523 (Unable to list file types.  Please report this issue)";
        }

        while ($row = $res->fetch_object()) {
            $results[$row->ftype] = $row->fcount;
        }
        $stmt->close();

        return $results;
    }

    public function stats_get_top_rules()
    {
        $results = array();

        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("select yara->'$.yara' as rules from $table WHERE added > (unix_timestamp() - 86400)");
        if (! $stmt) {
            return "Error 132621 (Unable to list file types.  Please report this issue)";
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            return "Error 132621 (Unable to list file types.  Please report this issue)";
        }
        if ($res->num_rows == 0) {
            $stmt->close();
            return "Error 132622 (Unable to list file types.  Please report this issue)";
        }

        while ($row = $res->fetch_object()) {
            $rules = null;
            $rules_json = $row->rules ?? '';
            if ($rules_json !== null && $rules_json !== '') {
                $rules = json_decode($rules_json);
            }
            if ($rules && is_array($rules)) {
                foreach ($rules as $yhit) {
                    array_push($results, $yhit);
                }
            }
        }
        $stmt->close();

        $totals = array_count_values($results);
        arsort($totals);
        $totals = array_slice($totals, 0, 10);

        return $totals;
    }

    public function get_recent_searches()
    {
        $results = array();

        $table = $this->vars_table_pub_searches;
        $stmt = $this->sql->prepare("SELECT query from $table  ORDER BY ts DESC limit 10");
        if (! $stmt) {
            return $results;
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            return $results;
        }
        if ($res->num_rows == 0) {
            $stmt->close();
            return $results;
        }

        while ($row = $res->fetch_object()) {
            array_push($results, $row->query);
        }
        $stmt->close();

        return $results;
    }

    public function get_samples_count_date()
    {
        $results = array();

        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT FROM_UNIXTIME(added, \"%Y-%m-%d\") AS date, COUNT(*) AS sampleCount FROM $table WHERE ( added > ( unix_timestamp(now()) - 604800  ) )  GROUP BY FROM_UNIXTIME(added, \"%Y-%m-%d\") ORDER BY sampleCount DESC;");
        if (! $stmt) {
            return $results;
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (! $res) {
            $stmt->close();
            return $results;
        }
        if ($res->num_rows == 0) {
            $stmt->close();
            return $results;
        }

        while ($row = $res->fetch_object()) {
            array_push($results, array($row->date, $row->sampleCount));
        }
        $stmt->close();

        return $results;
    }

    public function sha256ExistsInDatabase($sha256): bool
    {
        $sql = "SELECT sha256 FROM {$this->vars_table_samples} where sha256 = ? limit 1";
        if (! ($stmt = $this->sql->prepare($sql))) {
            $this->error_die("Error 148993 (Please report this issue)");
        }
        $stmt->bind_param('s', $sha256);
        $stmt->execute();
        $stmt->bind_result($hash);

        return !!$stmt->fetch();
    }

    public function upload_sample($uploadedSample): array
    {
        $tmpPath = $uploadedSample['tmp_name'];
        if (!$tmpPath) {
            return ['type' => 'error', 'message' => 'No file specified'];
        }
        $md5 = strtolower(hash_file("md5", "$tmpPath"));
        $sha1 = strtolower(hash_file("sha1", "$tmpPath"));
        $sha156 = strtolower(hash_file("sha256", "$tmpPath"));
        $s3Key = $this->sample_key($sha156);

        $remoteAddress = $this->secure($_SERVER['REMOTE_ADDR']);
        $clientFileName = $this->secure($uploadedSample['name']);

        try {
            $sql = "INSERT INTO {$this->vars_table_uploads} (name, md5, source, ts) VALUES (?, ?, ?, UNIX_TIMESTAMP())";
            if (! ($stmt = $this->sql->prepare($sql))) {
                return ['type' => 'error', 'message' => 'Error 148992. Please report this issue'];
            }
            $stmt->bind_param('sss', $clientFileName, $md5, $remoteAddress);
            $stmt->execute();

            if ($this->sha256ExistsInDatabase($sha156)) {
                if ($this->s3Client->doesObjectExist(WASABI_BUCKET, $s3Key)) {
                    return ['type' => 'success', 'sha256' => $sha156, 'message' => 'sample already exists'];
                }
            }

            $this->s3Client->putObject(['Bucket' => WASABI_BUCKET, 'Key' => $s3Key, 'SourceFile' => $tmpPath]);

            $sql = "INSERT INTO {$this->vars_table_samples} (md5, sha1, sha256, added, counter, pending,ftype) VALUES (?, ?, ?, UNIX_TIMESTAMP(), 0, 1, '-')";
            if (! ($stmt = $this->sql->prepare($sql))) {
                return ['type' => 'error', 'message' => 'Error 139999. Please report this issue'];
            }
            $stmt->bind_param('sss', $md5, $sha1, $sha156);
            $stmt->execute();
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        return ['type' => 'success', 'sha256' => $sha156];
    }

    public function task_url_download($user_id, $durl, $drecursive)
    {
        $table = $this->vars_table_url_download_tasks;

        $recursive = $this->secure($drecursive);
        $url = $this->secure($durl);

        # https://stackoverflow.com/questions/21671179/how-to-generate-a-new-guid
        $guid = vsprintf(
            '%s%s-%s-4000-8%.3s-%s%s%s0',
            str_split(dechex(microtime(true) * 1000) . bin2hex(random_bytes(8)), 4)
        );

        if ($recursive != 1) {
            $recursive = 0;
        }
        if (! ($stmt = $this->sql->prepare("INSERT INTO $table (guid, user_id, url, fetchall) VALUES (?, ?, ?, ? )"))) {
            $this->error_die("Error 149991 (URL Tasking failed. Please report this issue)");
            return "false";
        }
        $stmt->bind_param('sisi', $guid, $user_id, $url, $recursive);
        if (! $stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 149991 (URL Tasking failed. Please report this issue)");

            return "false";
        }
        $stmt->close();

        return $guid;
    }

    public function is_valid_guid($guid)
    {
        if (! preg_match("/^[A-Fa-f0-9]{8}\-[A-Fa-f0-9]{4}\-4000-8[A-Fa-f0-9]{3}\-[A-Fa-f0-9]{12}$/", $guid)) {
            return false;
        }

        return true;
    }

    public function get_download_status($userId, $guid)
    {
        $table = $this->vars_table_url_download_tasks;
        $sql = 'SELECT started_at, finished_at FROM ' . $table . ' WHERE (guid = ?) AND (user_id = ?)';
        if (! ($stmt = $this->sql->prepare($sql))) {
            $this->error_die(
                "Error 149992 (Problem fetching URL Download task status.  Please report this issue)"
            );
        }
        $stmt->bind_param('si', $guid, $userId);
        $stmt->execute();
        $stmt->bind_result($startedAt, $finishedAt);
        if (! $stmt->fetch()) {
            return 'missing';
        }
        if ($this->empty_date_str($startedAt)) {
            return 'pending';
        } elseif ($this->empty_date_str($finishedAt)) {
            return 'processing';
        } else {
            return 'finished';
        }
    }

    private function empty_date_str($str)
    {
        return ! $str || ($str === '1970-01-01 01:00:01') || ($str === '1970-01-01 00:00:01');
    }
}
