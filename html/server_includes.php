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
define("API_CALLS_TABLE", "tbl_api_calls");
define("API_CALLS_DAILY_TABLE", "tbl_api_calls_daily");

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
    public $is_admin;
    public $ready;

    function __construct($sql, $submitted_api_key, $web = false)
    {
        $this->ready = false;
        if (!($stmt = $sql->prepare('SELECT id as id, api_key as api_key, active as active, approved as approved, recursive_url_download_allowed, is_admin FROM tbl_users WHERE api_key = ? LIMIT 1'))) {
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
            $this->is_admin = false;
        } else {
            $this->id = $row->id;
            $this->api_key = $row->api_key;
            $this->active = $row->active;
            $this->approved = $row->approved;
            $this->recursiveUrlDownloadAllowed = $row->recursive_url_download_allowed;
            $this->is_admin = (bool) $row->is_admin;
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
    public static function client_ip(): string
    {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    }

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
    public $vars_table_public_searches;
    public $vars_table_uploads;
    public $vars_table_url_download_tasks;
    public $vars_table_sample_partners;
    public $vars_table_api_calls;
    public $vars_table_api_calls_daily;

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
        $this->host_ip = self::client_ip();

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
            if (!preg_match('/^[A-Za-z0-9]{1,100}$/', $this->uri_api_key)) {
                http_response_code(400);
                die("No API Key Supplied");
            }
        }

        // Tables
        $this->vars_table_samples = SAMPLES_TABLE;
        $this->vars_table_users = USERS_TABLE;
        $this->vars_table_sources = SAMPLE_SOURCES_TABLE;
        $this->vars_table_searches = SEARCHES_TABLE;
        $this->vars_table_public_searches = PUBSEARCHES_TABLE;
        $this->vars_table_uploads = UPLOADS_TABLE;
        $this->vars_table_url_download_tasks = URLDLTASKS_TABLE;
        $this->vars_table_sample_partners = SAMPLE_PARTNER_TABLE;
        $this->vars_table_api_calls = API_CALLS_TABLE;
        $this->vars_table_api_calls_daily = API_CALLS_DAILY_TABLE;

        $this->vt_context_key = VT_CONTEXT_KEY;
        $this->vt_context_url = VT_CONTEXT_URL;
    }

    public function login(): UserObject
    {
        return new UserObject($this->sql, $this->uri_api_key, true);
    }

    public function secure($string)
    {
        if (!$this->sql) {
            die("ERROR");
        }
        // Ensure we never pass null to strip_tags (PHP 8.1+ deprecation)
        $string = strip_tags((string)$string);
        $string = stripslashes($string);

        // Strip non-ASCII characters to avoid collation conversion issues
        $string = preg_replace('/[^\x00-\x7F]/', '', $string);

        return $this->sql->real_escape_string($string);
    }

    public function escape_html($string): string
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

    public static function github_issue_url($error_code, $description = ''): string
    {
        $title = rawurlencode("Error $error_code");
        $url = $_SERVER['REQUEST_URI'] ?? '';
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

    public static function error_message_html($string): string
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

    public function get_recent(): string
    {
        $table = $this->vars_table_samples;
        $table_sources = $this->vars_table_sources;
        $table_sample_partners = $this->vars_table_sample_partners;

        $stmt = $this->sql->prepare("SELECT s.sha256, s.added, s.ftype, ts.source, tsp.display_name AS source_display_name FROM (SELECT id, sha256, added, ftype FROM $table WHERE (pending != 1 OR pending IS NULL) AND ftype != 'html' ORDER BY added DESC LIMIT 10) s LEFT JOIN $table_sources ts ON s.id = ts.id LEFT JOIN $table_sample_partners tsp ON ts.sample_partner_submission = tsp.id ORDER BY s.added DESC");
        if (!$stmt) {
            $this->error_die("Error 13513 (Unable to get recent samples. Please report this issue)");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
            $stmt->close();
            $this->error_die("Error 13513 (Unable to get recent samples. Please report this issue)");
        }

        $output = '<table class="table table-bordered table-striped" style="table-layout: fixed;">
        <thead>  <tr>
        <th style="width: 17%;">SHA256 Hash</th>
        <th style="width: 5%">File type</th>
        <th style="width: 13%">Added</th>
        <th style="width: 25%">Source</th>
        </tr>  </thead>  <tbody>';

        while ($sample_row = $res->fetch_object()) {
            $output .= '<tr>
                    <td class="hash_font"><div style = "word-wrap: break-word"><a href="sample.php?action=detail&hash=' . $sample_row->sha256 . '">' . $sample_row->sha256 . '</a></div></td>
                    <td>' . $sample_row->ftype . '</td>
                    <td>' . gmdate("Y-m-d H:i:s", $sample_row->added) . ' UTC</td>';

            $output .= '<td class="word-wrap: wrap-word">' . $this->sourceForDisplay($sample_row) . '</td></tr>';

        }
        $output .= '</tbody></table>';
        $stmt->close();

        return $output;
    }

    public function sourceForDisplay($row, $separator = ' | '): string
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

    public function get_sitemap(): string
    {
        $output = "";
        $table = $this->vars_table_samples;
        $sql = "SELECT md5, sha1, sha256 FROM $table WHERE ( pending != 1 or pending is NULL ) ORDER by added DESC limit 1000";
        if (!($stmt = $this->sql->prepare($sql))) {
            $this->error_die("Error 23214 (Problem building sitemap.  Please report this issue)");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
            $stmt->close();
            $this->error_die("Error 23214 (Problem building sitemap.  Please report this issue)");
        }

        while ($sample_row = $res->fetch_object()) {
            $output .= '<a href="sample.php?action=detail&hash=' . $sample_row->sha256 . '">' . $sample_row->md5 . ' | ' . $sample_row->sha1 . ' | ' . $sample_row->sha256 . '</a><br />';
        }
        $stmt->close();

        return $output;
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
        if (!($stmt = $this->sql->prepare($sql))) {
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
        $table_sources = $this->vars_table_sources;
        $table_searches = $this->vars_table_searches;
        $table_pub_searches = $this->vars_table_public_searches;
        $table_sample_partners = $this->vars_table_sample_partners;

        $searchValue = $this->secure($this->uri_query);
        $searchPrivate = 0;
        $source_ip = $this->secure(self::client_ip());
        if ($this->secure($this->uri_private) == "on") {
            $searchPrivate = 1;
        }

        if (strlen($searchValue) < 3) {
            $this->error_die("Query must by longer then 3 characters");
        }

        if ($stmt = $this->sql->prepare("INSERT INTO $table_searches (query, source, ts, private) VALUES (?, ?, UNIX_TIMESTAMP(), ?)")) {
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
                $searchValueLower = strtolower($searchValue);
                $like = $searchValueLower . '%';
                $stmt = $this->sql->prepare("SELECT id FROM tbl_sample_sources WHERE source LIKE ? LIMIT 100");
                $stmt->bind_param('s', $like);
                $stmt->execute();
                $res = $stmt->get_result();
            }
        }

        if (!$res) {
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
        </tr>  </thead>  <tbody>';
        } else {
            header('Content-Type: application/json');
            $output = array();
        }
        // Collect IDs from initial search
        $ids = [];
        while ($s_row = $res->fetch_object()) {
            $ids[] = $s_row->id;
        }

        $totalHits = 0;

        if (!empty($ids)) {
            // Batch detail query
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            if (!($stmt = $this->sql->prepare(
                "SELECT
                       s.id AS id,
                       s.md5 AS md5,
                       s.sha1 AS sha1,
                       s.sha256 AS sha256,
                       s.added AS added,
                       s.ftype AS ftype,
                       ts.source AS source,
                       tsp.display_name AS display_name_source,
                       s.parent_id
                FROM {$table} s
                    LEFT JOIN {$table_sources} ts ON s.id = ts.id
                    LEFT JOIN {$table_sample_partners} tsp ON ts.sample_partner_submission = tsp.id
                WHERE s.id IN ($placeholders)"
            ))) {
                $this->error_die("Error 13842 (Problem fetching search results.  Please report this issue)");
            }
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $detail_res = $stmt->get_result();
            if (!$detail_res) {
                $stmt->close();
                $this->error_die("Error 13842 (Problem fetching search results.  Please report this issue)");
            }
            $details = [];
            while ($row = $detail_res->fetch_object()) {
                if (!isset($details[$row->id])) {
                    $details[$row->id] = $row;
                }
            }
            $stmt->close();

            // For API queries, batch parent and child lookups
            $parent_details = [];
            $children_by_parent = [];
            if (!$notAnApiQuery) {
                // Collect all unique parent IDs
                $all_parent_ids = [];
                foreach ($ids as $id) {
                    if (!isset($details[$id])) continue;
                    $sr = $details[$id];
                    if ($sr->parent_id != null) {
                        $pids = (strpos($sr->parent_id, ',') !== false)
                            ? explode(",", $sr->parent_id)
                            : array($sr->parent_id);
                        foreach ($pids as $pid) {
                            $all_parent_ids[intval($pid)] = true;
                        }
                    }
                }

                // Batch fetch parent samples
                if (!empty($all_parent_ids)) {
                    $parent_id_list = array_keys($all_parent_ids);
                    $p_ph = implode(',', array_fill(0, count($parent_id_list), '?'));
                    $p_types = str_repeat('i', count($parent_id_list));
                    if ($pstmt = $this->sql->prepare("SELECT id, md5, sha1, sha256 FROM $table WHERE id IN ($p_ph)")) {
                        $pstmt->bind_param($p_types, ...$parent_id_list);
                        $pstmt->execute();
                        $p_res = $pstmt->get_result();
                        if ($p_res) {
                            while ($p_row = $p_res->fetch_object()) {
                                $parent_details[$p_row->id] = array('md5' => $p_row->md5, 'sha1' => $p_row->sha1, 'sha256' => $p_row->sha256);
                            }
                        }
                        $pstmt->close();
                    }
                }

                // Batch fetch child samples
                $c_ph = implode(',', array_fill(0, count($ids), '?'));
                $c_types = str_repeat('i', count($ids));
                if ($cstmt = $this->sql->prepare("SELECT parent_id, md5, sha1, sha256 FROM $table WHERE parent_id IN ($c_ph)")) {
                    $cstmt->bind_param($c_types, ...$ids);
                    $cstmt->execute();
                    $c_res = $cstmt->get_result();
                    if ($c_res) {
                        while ($c_row = $c_res->fetch_object()) {
                            $children_by_parent[$c_row->parent_id][] = array('md5' => $c_row->md5, 'sha1' => $c_row->sha1, 'sha256' => $c_row->sha256);
                        }
                    }
                    $cstmt->close();
                }
            }

            // Build output in original ID order
            foreach ($ids as $id) {
                if (!isset($details[$id])) continue;
                $sample_row = $details[$id];
                $totalHits += 1;
                $source = $this->sourceForDisplay($sample_row, '<br/>');

                if ($notAnApiQuery) {
                    $output .= '<tr>
                    <td class="hash_font"><div style = "word-wrap: break-word"><a href="sample.php?action=detail&hash=' . $sample_row->sha256 . '">' . $sample_row->sha256 . '</a></div></td>
                    <td>' . $sample_row->ftype . '</td>
                    <td>' . gmdate("Y-m-d H:i:s", $sample_row->added) . '</td>';

                    if (strlen($source) > 45) {
                        $output .= '<td>' . substr($source, 0, 45) . '...</td> ';
                    } else {
                        $output .= '<td>' . $source . '</td> ';
                    }

                    $output .= '</tr>';
                } else {
                    $t = array(
                        'md5' => $sample_row->md5,
                        'sha1' => $sample_row->sha1,
                        'sha256' => $sample_row->sha256,
                        'type' => $sample_row->ftype,
                        'added' => intval($sample_row->added),
                        'source' => $source,
                        'parentfiles' => array(),
                        'subfiles' => array(),
                    );

                    if ($sample_row->parent_id != null) {
                        $pids = (strpos($sample_row->parent_id, ',') !== false)
                            ? explode(",", $sample_row->parent_id)
                            : array($sample_row->parent_id);
                        foreach ($pids as $pid) {
                            $pid = intval($pid);
                            if (isset($parent_details[$pid])) {
                                $t['parentfiles'][] = $parent_details[$pid];
                            }
                        }
                    }

                    if (isset($children_by_parent[$id])) {
                        $t['subfiles'] = $children_by_parent[$id];
                    }

                    array_push($output, $t);
                }
            }
        }

        if (!$api_query && ($totalHits > 0) && ($searchPrivate == 0)) {
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
            return '<br /> <center><p class="lead">Sample not found with hash ( ' . $this->escape_html($hash) . ' )</p></center>';
        }

        $row = (object)['hash' => $id];

        if (!($stmt = $this->sql->prepare("SELECT md5, sha1, sha256, ssdeep, added, ftype, pending, parent_id FROM $table WHERE id = ?"))) {
            $this->error_die("Error 23418 (Unable to find child samples  Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (!$full_res || $full_res->num_rows == 0) {
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
                <td class="hash_font"><b>Added</b>:   ' . gmdate("Y-m-d H:i:s", $f_row->added) . '</td>
              </tr>
            </tbody>
            </table>
        ';
        if (!($fname_stmt = $this->sql->prepare("SELECT DISTINCT name FROM $table_uploads WHERE md5 = ?"))) {
            $this->error_die("Error 23428 (Unable to find file names  Please report this issue)");
        }
        $fname_stmt->bind_param('s', $f_row->md5);
        $fname_stmt->execute();
        $fname_search = $fname_stmt->get_result();
        if (!$fname_search) {
            $fname_stmt->close();
            $this->error_die("Error 23428 (Unable to find file names  Please report this issue)");
        }
        $hash_values = array_map('strtolower', [$f_row->md5, $f_row->sha1, $f_row->sha256]);
        $filename_blocklist = ['upload'];
        $filtered_names = [];
        while ($trow = $fname_search->fetch_object()) {
            $normalized = strtolower(trim($trow->name));
            if (!in_array($normalized, $hash_values, true) && !in_array($normalized, $filename_blocklist, true)) {
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
                if (!($pstmt = $this->sql->prepare("SELECT sha256 FROM $table WHERE id = ?"))) {
                    $this->error_die("Error 23732 (Problem finding parent details for hash.  Please report this issue)");
                }
                $pstmt->bind_param('i', $pid);
                $pstmt->execute();
                $full_res = $pstmt->get_result();
                if (!$full_res) {
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
        if (!($cstmt = $this->sql->prepare("SELECT sha256 FROM $table WHERE parent_id = ?"))) {
            $this->error_die("Error 23734 (Problem finding child samples.  Please report this issue)");
        }
        $cstmt->bind_param('i', $row->hash);
        $cstmt->execute();
        $full_res = $cstmt->get_result();
        if (!$full_res) {
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

        if (!($stmt = $this->sql->prepare(
            "SELECT ts.source AS source, tsp.display_name AS source_display_name FROM $table_sources ts LEFT JOIN $table_sample_partners tsp ON ts.sample_partner_submission = tsp.id WHERE ts.id = ?"
        ))) {
            $this->error_die("Error 23735 (Problem finding sources for sample.  Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (!$full_res) {
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

        if (!($stmt = $this->sql->prepare("SELECT md5, sha1, sha256, ssdeep, added, ftype FROM $table WHERE id = ?"))) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724341;
            $output['ERROR']["MESSAGE"] = "problem getting details for hash (json).  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (!$full_res || $full_res->num_rows == 0) {
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

        if (!($stmt = $this->sql->prepare("SELECT source FROM $table_sources WHERE id = ?"))) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724323;
            $output['ERROR']["MESSAGE"] = "Problem getting sources for hash.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (!$full_res) {
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

        if (!($nstmt = $this->sql->prepare("SELECT name FROM $table_uploads WHERE md5 = ?"))) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 724323;
            $output['ERROR']["MESSAGE"] = "Problem getting sources for hash.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $nstmt->bind_param('s', $f_row->md5);
        $nstmt->execute();
        $name_res = $nstmt->get_result();
        if (!$name_res) {
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

    public function get_hashes(array $hashes): array
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
        if (!$where) {
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

        if (!$clauses) {
            return [];
        }

        $sql = 'SELECT sha256, md5, sha1 FROM ' . $this->vars_table_samples . ' WHERE (' . implode(' OR ', $clauses) . ')';
        if (!($stmt = $this->sql->prepare($sql))) {
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

    private function sample_key($hash): string
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
        if (!($stmt = $this->sql->prepare("SELECT sha256 AS hash, md5 AS md5 FROM {$this->vars_table_samples} WHERE $searchFieldName = lower(?)"))) {
            $this->error_die("Error 13940 (Problem finding sample. Please report this issue)");
        }
        $stmt->bind_param('s', $clean);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows == 0) {
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
        if (!$row->hash) {
            http_response_code(400);
            die("No hash specified in `hash` GET variable");
        }

        $s3Key = $this->sample_key($row->hash);

        if (!$this->s3Client->doesObjectExist(WASABI_BUCKET, $s3Key)) {
            http_response_code(404);
            $this->error_die_with_code(404, "Error 12413 (Sample Missing. Please report this issue)");
        }
        $cmd = $this->s3Client->getCommand('GetObject', ['Bucket' => WASABI_BUCKET, 'Key' => $s3Key]);
        $request = $this->s3Client->createPresignedRequest($cmd, '+5 minutes');

        return (string)$request->getUri();
    }

    public function get_list()
    {
        header('Content-Type: application/json');
        $output = array();

        $table = $this->vars_table_samples;
        $stmt = $this->sql->prepare("SELECT md5 as md5, sha1 as sha1, sha256 as sha256 FROM $table WHERE ( added > ( UNIX_TIMESTAMP() - 86400) )");
        if (!$stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 131312;
            $output['ERROR']["MESSAGE"] = "Unable to generate sample list.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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
        if (!$stmt) {
            $this->error_die("Error 131311 (Please report this issue)");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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


        if (!($stmt = $this->sql->prepare("SELECT md5, sha1, sha256, ssdeep, added, ftype FROM $table WHERE id = ?"))) {
            $this->error_die("Error 139432 (Problem getting sample details. Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (!$full_res || $full_res->num_rows == 0) {
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

        if (!($stmt = $this->sql->prepare("SELECT source FROM $table_sources WHERE id = ?"))) {
            $this->error_die("Error 139312 (Problem sample sources. Please report this issue)");
        }
        $stmt->bind_param('i', $row->hash);
        $stmt->execute();
        $full_res = $stmt->get_result();
        if (!$full_res || $full_res->num_rows == 0) {
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
        if (!$stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 139001;
            $output['ERROR']["MESSAGE"] = "Problem pulling sample count.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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
        if (!$stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 131132;
            $output['ERROR']["MESSAGE"] = "Problem pulling results for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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
        if (!$stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138523;
            $output['ERROR']["MESSAGE"] = "Problem pulling types from the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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
        $stmt = $this->sql->prepare("SELECT distinct name as name FROM $table WHERE ( ts > ( UNIX_TIMESTAMP()-86400) and ts is not NULL ) LIMIT 1000");
        if (!$stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138043;
            $output['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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
        $stmt = $this->sql->prepare("SELECT distinct source as source FROM $table WHERE ( added > ( UNIX_TIMESTAMP()-86400) and added is not NULL ) LIMIT 1000");
        if (!$stmt) {
            http_response_code(500);
            $output['ERROR'] = array();
            $output['ERROR']["CODE"] = 138023;
            $output['ERROR']["MESSAGE"] = "Problem pulling sources for the past day.  Please report this issue";

            return json_encode($output, JSON_UNESCAPED_SLASHES);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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
        $stmt = $this->sql->prepare("SELECT distinct source as source FROM $table WHERE ( added > ( UNIX_TIMESTAMP()-86400) and added is not NULL ) LIMIT 1000");
        if (!$stmt) {
            $this->error_die(
                "Error 138024. (Problem pulling raw source list for the past day. Please report this issue)"
            );
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
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

    /**
     * Lazy-loaded, memoized list of disposable-email domains used to
     * reject obviously throwaway addresses during registration.
     */
    private function disposable_email_domains(): array
    {
        static $domains = null;
        if ($domains === null) {
            $domains = require __DIR__ . '/include/disposable_email_domains.php';
        }
        return $domains;
    }

    /**
     * Generate a fresh API key. 64 hex chars (sha256 of random bytes).
     */
    public function generate_api_key(): string
    {
        $pre_rand = base64_encode(openssl_random_pseudo_bytes(32));
        return hash('sha256', $pre_rand);
    }

    /**
     * Send the registration confirmation email via Mailgun SMTP.
     * Returns true on success, false on Mailgun/PEAR failure.
     */
    public function send_register_email(string $name, string $email, string $api_key): bool
    {
        require_once 'Mail.php';

        $to = $email;
        $subject = 'Malshare API Key';
        $body = '
Thank you for your interest in the MalShare research project. Below, you\'ll find your registrant name, email, and API key.

Name    : ' . $name . '
Email   : ' . $email . '
API Key : ' . $api_key . '


Your free API key will allow you to pull 2000 samples per day. If you require more or have additional feature requests, please open a request at https://github.com/Malshare/MalShare/issues

If you would like to show your support for the MalShare Project, please consider donating via paypal.

Donate    : www.malshare.com/donate.php
Resources : https://github.com/malshare

The MalShare Project Team
www.malshare.com
';

        $host = getenv('MALSHARE_MAILGUN_SMTP');
        $port = intval(getenv('MALSHARE_MAILGUN_PORT'));
        $from = getenv('MALSHARE_MAILGUN_FROM');
        $username = getenv('MALSHARE_MAILGUN_USERNAME');
        $password = getenv('MALSHARE_MAILGUN_PASSWORD');

        $headers = [
            'From' => $from,
            'To' => $to,
            'Subject' => $subject,
        ];
        $smtp = Mail::factory('smtp', [
            'host' => $host,
            'port' => $port,
            'auth' => true,
            'username' => $username,
            'password' => $password,
        ]);

        $mail = $smtp->send($to, $headers, $body);

        if (PEAR::isError($mail)) {
            return false;
        }
        return true;
    }

    /**
     * Register a new user. Validates the email, rejects disposable
     * domains, and either inserts a new tbl_users row + sends the API
     * key by email, or (if the email already exists) resends the
     * existing key. Returns a structured result so the caller can
     * render the appropriate UI.
     *
     * Result shape:
     *   ['ok' => bool,
     *    'error' => null|'invalid_email'|'disposable_email'|'already_registered'|'db_error',
     *    'email' => string,        // sanitized email, safe to display
     *    'email_sent' => bool]     // whether Mailgun accepted the message
     */
    public function register_user(string $name, string $email): array
    {
        // 1. Sanitize. strip_tags removes any HTML; ServerObject::secure()
        // strips non-ASCII and applies real_escape_string. HTML escaping
        // for display happens at output time via h()/escape_html().
        $email = preg_replace('/\s+/', '',
            filter_var(strip_tags($this->secure($email)), FILTER_SANITIZE_EMAIL));
        $name = strip_tags($this->secure($name));

        // 2. Validate format.
        if (!preg_match('/^[A-Za-z0-9\.\-\_\+]*@[A-Za-z0-9\.\-\_]+$/', $email)) {
            return ['ok' => false, 'error' => 'invalid_email',
                'email' => $email, 'email_sent' => false];
        }

        // 3. Reject disposable-email domains.
        $parts = explode('@', $email);
        $domain = strtolower(array_pop($parts));
        if (in_array($domain, $this->disposable_email_domains(), true)) {
            return ['ok' => false, 'error' => 'disposable_email',
                'email' => $email, 'email_sent' => false];
        }

        // 4. Already registered? Resend the existing key, surface as success.
        $stmt = $this->sql->prepare(
            'SELECT `name`, `email`, `api_key` FROM `tbl_users` WHERE `email` = ? LIMIT 1');
        if (!$stmt) {
            return ['ok' => false, 'error' => 'db_error',
                'email' => $email, 'email_sent' => false];
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_object();
            $stmt->close();
            $sent = $this->send_register_email($row->name, $row->email, $row->api_key);
            return ['ok' => true, 'error' => 'already_registered',
                'email' => $row->email, 'email_sent' => $sent];
        }
        $stmt->close();

        // 5. Insert new user.
        $api_key = $this->generate_api_key();
        $ins = $this->sql->prepare(
            'INSERT INTO `tbl_users`(`name`, `email`, `api_key`, `approved`, `active`, `r_ip_address`)
             VALUES (?, ?, ?, 1, 1, ?)');
        if (!$ins) {
            return ['ok' => false, 'error' => 'db_error',
                'email' => $email, 'email_sent' => false];
        }
        $ins->bind_param('ssss', $name, $email, $api_key, $this->host_ip);
        if (!$ins->execute()) {
            $ins->close();
            return ['ok' => false, 'error' => 'db_error',
                'email' => $email, 'email_sent' => false];
        }
        $ins->close();

        // 6. Send welcome email.
        $sent = $this->send_register_email($name, $email, $api_key);
        return ['ok' => true, 'error' => null,
            'email' => $email, 'email_sent' => $sent];
    }

    public function terminate_api_key()
    {
        header('Content-Type: application/json');

        $table = $this->vars_table_users;
        $api_key = $this->secure($this->uri_api_key);


        if (!($stmt = $this->sql->prepare("UPDATE $table SET active = 0 WHERE api_key = ?"))) {
            $this->error_die("Error 432999 (Please report this issue)");
        }
        $stmt->bind_param('s', $api_key);
        if (!$stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 432999 (Please report this issue)");
        }
        $stmt->close();

        $output["message"] = "Thank you";

        return json_encode($output, JSON_UNESCAPED_SLASHES);
    }

    public function get_user_quota($api_key)
    {
        $table = $this->vars_table_users;
        if (!($stmt = $this->sql->prepare("SELECT query_limit, query_base FROM $table WHERE api_key = ?"))) {
            return null;
        }
        $stmt->bind_param('s', $api_key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_object() : null;
        if ($row === null) {
            return null;
        }

        return array('limit' => (int) $row->query_base, 'remaining' => (int) $row->query_limit);
    }

    public function get_user_limit()
    {
        header('Content-Type: application/json');
        $quota = $this->get_user_quota($this->uri_api_key);
        if ($quota === null) {
            http_response_code(500);
            return json_encode(array('ERROR' => array(
                'CODE' => 439021,
                'MESSAGE' => 'Unable to fetch limits.  Please report this issue',
            )), JSON_UNESCAPED_SLASHES);
        }

        return json_encode(array(
            'LIMIT' => $quota['limit'],
            'REMAINING' => $quota['remaining'],
        ), JSON_UNESCAPED_SLASHES);
    }

    public function update_query_limit()
    {
        $table = $this->vars_table_users;
        $api_key = $this->uri_api_key;
        if (!($stmt = $this->sql->prepare("SELECT query_limit, last_query FROM $table WHERE api_key = ?"))) {
            $this->error_die("Error 432101 (Please report this issue)");
        }
        $stmt->bind_param('s', $api_key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_object() : null;

        if (!$row) {
            $this->error_die("Error 432101 (Please report this issue)");
        }

        if ($row->query_limit <= 0) {
            if (($row->last_query + 86400) < time()) {
                if (!($u = $this->sql->prepare("UPDATE $table SET query_limit = query_base - 1 WHERE api_key = ?"))) {
                    $this->error_die("Error 432103 (Please report this issue)");
                }
                $u->bind_param('s', $api_key);
                if (!$u->execute()) {
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
            if (!($u = $this->sql->prepare("UPDATE $table SET query_limit = query_limit - 1, last_query = UNIX_TIMESTAMP() WHERE api_key = ?"))) {
                $this->error_die("Error 492104 (Please report this issue)");
            }
            $u->bind_param('s', $api_key);
            if (!$u->execute()) {
                $u->close();
                $this->error_die("Error 492104 (Please report this issue)");
            }
            $u->close();
        }
    }

    public function log_api_call($user_id, $endpoint)
    {
        $table = $this->vars_table_api_calls;
        $ts = time();
        if (!($stmt = $this->sql->prepare("INSERT INTO $table (user_id, endpoint, ts) VALUES (?, ?, ?)"))) {
            return;
        }
        $stmt->bind_param('isi', $user_id, $endpoint, $ts);
        $stmt->execute();
        $stmt->close();
    }

    public function get_api_calls_per_day($days = 30)
    {
        $t = $this->vars_table_api_calls;
        $td = $this->vars_table_api_calls_daily;
        $midnight = strtotime('today');
        $since = $midnight - ($days * 86400);
        $since_date = gmdate('Y-m-d', $since);
        $now = time();
        $sql = "SELECT day_label, SUM(cnt) AS total FROM ("
             . "SELECT FLOOR(ts / 86400) AS day_label, COUNT(*) AS cnt FROM $t WHERE ts >= ? AND ts <= ? GROUP BY day_label "
             . "UNION ALL "
             . "SELECT TO_DAYS(day) AS day_label, call_count AS cnt FROM $td WHERE day >= ?"
             . ") combined GROUP BY day_label ORDER BY day_label";
        if (!($stmt = $this->sql->prepare($sql))) {
            return [];
        }
        $stmt->bind_param('iis', $since, $now, $since_date);
        $stmt->execute();
        $stmt->bind_result($dayBucket, $count);
        $ret = [];
        while ($stmt->fetch()) {
            $ret[] = ['date' => gmdate('Y-m-d', $dayBucket * 86400), 'count' => (int) $count];
        }
        $stmt->close();
        return $ret;
    }

    public function get_api_calls_per_month($months = 12)
    {
        $t = $this->vars_table_api_calls;
        $td = $this->vars_table_api_calls_daily;
        $since = strtotime("-$months months midnight");
        $since_date = gmdate('Y-m-d', $since);
        $sql = "SELECT month_label, SUM(cnt) AS total FROM ("
             . "SELECT DATE_FORMAT(FROM_UNIXTIME(ts), '%Y-%m') AS month_label, COUNT(*) AS cnt FROM $t WHERE ts >= ? GROUP BY month_label "
             . "UNION ALL "
             . "SELECT DATE_FORMAT(day, '%Y-%m') AS month_label, call_count AS cnt FROM $td WHERE day >= ?"
             . ") combined GROUP BY month_label ORDER BY month_label";
        if (!($stmt = $this->sql->prepare($sql))) {
            return [];
        }
        $stmt->bind_param('is', $since, $since_date);
        $stmt->execute();
        $stmt->bind_result($month, $count);
        $ret = [];
        while ($stmt->fetch()) {
            $ret[] = ['month' => $month, 'count' => (int) $count];
        }
        $stmt->close();
        return $ret;
    }

    public function get_api_calls_by_endpoint($days = 30)
    {
        $t = $this->vars_table_api_calls;
        $td = $this->vars_table_api_calls_daily;
        $since = time() - ($days * 86400);
        $since_date = gmdate('Y-m-d', $since);
        $sql = "SELECT endpoint, SUM(cnt) AS total FROM ("
             . "SELECT endpoint, COUNT(*) AS cnt FROM $t WHERE ts >= ? GROUP BY endpoint "
             . "UNION ALL "
             . "SELECT endpoint, call_count AS cnt FROM $td WHERE day >= ?"
             . ") combined GROUP BY endpoint ORDER BY total DESC";
        if (!($stmt = $this->sql->prepare($sql))) {
            return [];
        }
        $stmt->bind_param('is', $since, $since_date);
        $stmt->execute();
        $stmt->bind_result($endpoint, $count);
        $ret = [];
        while ($stmt->fetch()) {
            $ret[] = ['endpoint' => $endpoint, 'count' => (int) $count];
        }
        $stmt->close();
        return $ret;
    }

    public function get_api_top_users($days = 30, $limit = 20)
    {
        $t = $this->vars_table_api_calls;
        $td = $this->vars_table_api_calls_daily;
        $users_table = $this->vars_table_users;
        $since = time() - ($days * 86400);
        $since_date = gmdate('Y-m-d', $since);
        $sql = "SELECT u.id, u.name, u.email, SUM(c.cnt) AS total FROM ("
             . "SELECT user_id, COUNT(*) AS cnt FROM $t WHERE ts >= ? GROUP BY user_id "
             . "UNION ALL "
             . "SELECT user_id, call_count AS cnt FROM $td WHERE day >= ? AND user_id IS NOT NULL GROUP BY user_id, call_count"
             . ") c JOIN $users_table u ON c.user_id = u.id GROUP BY u.id, u.name, u.email ORDER BY total DESC LIMIT ?";
        if (!($stmt = $this->sql->prepare($sql))) {
            return [];
        }
        $stmt->bind_param('isi', $since, $since_date, $limit);
        $stmt->execute();
        $stmt->bind_result($id, $name, $email, $count);
        $ret = [];
        while ($stmt->fetch()) {
            $ret[] = ['id' => (int) $id, 'name' => $name, 'email' => $email, 'count' => (int) $count];
        }
        $stmt->close();
        return $ret;
    }

    public function get_api_calls_total($days = null)
    {
        $t = $this->vars_table_api_calls;
        $td = $this->vars_table_api_calls_daily;
        if ($days === null) {
            $sql = "SELECT (SELECT COUNT(*) FROM $t) + (SELECT COALESCE(SUM(call_count), 0) FROM $td)";
            if (!($stmt = $this->sql->prepare($sql))) {
                return 0;
            }
        } else {
            $since = time() - ($days * 86400);
            $since_date = gmdate('Y-m-d', $since);
            $sql = "SELECT (SELECT COUNT(*) FROM $t WHERE ts >= ?) + (SELECT COALESCE(SUM(call_count), 0) FROM $td WHERE day >= ?)";
            if (!($stmt = $this->sql->prepare($sql))) {
                return 0;
            }
            $stmt->bind_param('is', $since, $since_date);
        }
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return (int) $count;
    }

    public function get_last_rollup_date()
    {
        $td = $this->vars_table_api_calls_daily;
        if (!($stmt = $this->sql->prepare("SELECT MAX(day) FROM $td"))) {
            return null;
        }
        $stmt->execute();
        $stmt->bind_result($maxDay);
        $stmt->fetch();
        $stmt->close();
        return $maxDay;
    }

    public function increment_query_limit()
    {
        $table = $this->vars_table_users;
        $api_key = $this->uri_api_key;

        if (!($stmt = $this->sql->prepare("UPDATE $table SET query_limit = query_limit + 1 WHERE api_key = ?"))) {
            $this->error_die("Error 432114 (Please report this issue)");
        }
        $stmt->bind_param('s', $api_key);
        if (!$stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 432114 (Please report this issue)");
        }
        $stmt->close();
    }


    public function update_sample_count($hash)
    {
        $table = $this->vars_table_samples;
        if (!($stmt = $this->sql->prepare("UPDATE $table SET counter = counter + 1 WHERE md5 = ?"))) {
            $this->error_die("Error 432201 (Please report this issue)");
        }
        $stmt->bind_param('s', $hash);
        if (!$stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 432201 (Please report this issue)");
        }
        $stmt->close();
    }

    public function get_recent_searches(): array
    {
        $results = array();

        $table = $this->vars_table_public_searches;
        $stmt = $this->sql->prepare("SELECT query FROM $table ORDER BY ts DESC LIMIT 50");
        if (!$stmt) {
            return $results;
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res) {
            $stmt->close();
            return $results;
        }

        $seen = [];
        while ($row = $res->fetch_object()) {
            if (!isset($seen[$row->query])) {
                $seen[$row->query] = true;
                $results[] = $row->query;
                if (count($results) >= 10) break;
            }
        }
        $stmt->close();

        return $results;
    }

    public function sha256ExistsInDatabase($sha256): bool
    {
        $sql = "SELECT sha256 FROM {$this->vars_table_samples} where sha256 = ? limit 1";
        if (!($stmt = $this->sql->prepare($sql))) {
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

        $remoteAddress = $this->secure(self::client_ip());
        $clientFileName = $this->secure($uploadedSample['name']);

        try {
            $sql = "INSERT INTO {$this->vars_table_uploads} (name, md5, source, ts) VALUES (?, ?, ?, UNIX_TIMESTAMP())";
            if (!($stmt = $this->sql->prepare($sql))) {
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
            if (!($stmt = $this->sql->prepare($sql))) {
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

    public function task_url_download($user_id, $durl, $drecursive): string
    {
        $table = $this->vars_table_url_download_tasks;

        $recursive = $this->secure($drecursive);
        $url = $this->secure($durl);

        # https://stackoverflow.com/questions/21671179/how-to-generate-a-new-guid
        $guid = vsprintf(
            '%s%s-%s-4000-8%.3s-%s%s%s0',
            str_split(dechex((int)(microtime(true) * 1000)) . bin2hex(random_bytes(8)), 4)
        );

        if ($recursive != 1) {
            $recursive = 0;
        }
        if (!($stmt = $this->sql->prepare("INSERT INTO $table (guid, user_id, url, fetchall) VALUES (?, ?, ?, ? )"))) {
            $this->error_die("Error 149991 (URL Tasking failed. Please report this issue)");
        }
        $stmt->bind_param('sisi', $guid, $user_id, $url, $recursive);
        if (!$stmt->execute()) {
            $stmt->close();
            $this->error_die("Error 149991 (URL Tasking failed. Please report this issue)");
        }
        $stmt->close();

        return $guid;
    }

    public function is_valid_guid($guid): bool
    {
        if (!preg_match("/^[A-Fa-f0-9]{8}\-[A-Fa-f0-9]{4}\-4000-8[A-Fa-f0-9]{3}\-[A-Fa-f0-9]{12}$/", $guid)) {
            return false;
        }

        return true;
    }

    public function get_download_status($userId, $guid): array
    {
        $table = $this->vars_table_url_download_tasks;
        $sql = 'SELECT id, url, started_at, finished_at FROM ' . $table . ' WHERE (guid = ?) AND (user_id = ?)';
        if (!($stmt = $this->sql->prepare($sql))) {
            $this->error_die(
                "Error 149992 (Problem fetching URL Download task status.  Please report this issue)"
            );
        }
        $stmt->bind_param('si', $guid, $userId);
        $stmt->execute();
        $stmt->bind_result($taskId, $url, $startedAt, $finishedAt);
        if (!$stmt->fetch()) {
            return array('status' => 'missing', 'url' => '');
        }
        $stmt->close();

        $result = array('url' => $url);
        if ($this->empty_date_str($startedAt)) {
            $result['status'] = 'pending';
            $sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE id < ? AND started_at = \'1970-01-01 00:00:01\'';
            if (($stmt = $this->sql->prepare($sql))) {
                $stmt->bind_param('i', $taskId);
                $stmt->execute();
                $stmt->bind_result($ahead);
                $stmt->fetch();
                $stmt->close();
                $result['queue_position'] = $ahead;
            }
        } elseif ($this->empty_date_str($finishedAt)) {
            $result['status'] = 'processing';
        } else {
            $result['status'] = 'finished';
            $sha256 = $this->resolve_download_result_sha256($url, $startedAt);
            if ($sha256 !== null) {
                $result['sha256'] = $sha256;
            }
        }
        return $result;
    }

    /**
     * Look up the SHA256 of the sample produced by a finished URL-download task.
     *
     * The downloader writes results into tbl_sample_sources (source = the URL),
     * so we can resolve the resulting hash without storing it on the task row.
     * The added >= started_at filter avoids matching pre-existing rows for the
     * same URL from earlier submissions. Returns null if zero or more than one
     * sample matches, so the caller can fall back to a source: search.
     */
    private function resolve_download_result_sha256($url, $startedAt)
    {
        $startedTs = strtotime($startedAt);
        if ($startedTs === false) {
            return null;
        }
        $sources = $this->vars_table_sources;
        $samples = $this->vars_table_samples;
        $sql = "SELECT s.sha256 FROM $sources ss JOIN $samples s ON s.id = ss.id"
            . " WHERE ss.source = ? AND ss.added >= ? LIMIT 2";
        if (!($stmt = $this->sql->prepare($sql))) {
            return null;
        }
        $stmt->bind_param('si', $url, $startedTs);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $stmt->bind_result($sha256);
        $found = null;
        $count = 0;
        while ($stmt->fetch()) {
            $count++;
            if ($count === 1) {
                $found = $sha256;
            }
        }
        $stmt->close();
        return $count === 1 ? $found : null;
    }

    private function empty_date_str($str): bool
    {
        return !$str || ($str === '1970-01-01 01:00:01') || ($str === '1970-01-01 00:00:01');
    }
}
