<?php
use Aws\S3\S3Client;

/* ****************************************** */
/* Norman SampleShare Client Framework	*/
/* Version 1.30			       */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* ****************************************** */
/* Modified by Silas Cutler for Malshare.com  */
/* Version 1.0				*/
/*					    */
/* ****************************************** */


include("server_includes.php");

$share = new ServerObject();
if($share->uri_action=="") {
    http_response_code(400);
    die("No command specified in `action` GET variable");
}

if ($share->uri_api_key === null) {
    http_response_code(400);
    die("No key specified in `api_key` GET variable");
}
$user = new UserObject($share->sql, $share->uri_api_key);
if ($user->id === null) {
    http_response_code(400);
    die("User specified in `api_key` GET variable does not exist");
}

if($share->uri_action=="getlist" ) {
	$res = $share->update_query_limit();
	$contents = $share->get_list();
	echo $contents;
	die();
}
elseif($share->uri_action=="getlistraw" ) {
	$share->update_query_limit();
	$contents = $share->get_list_raw();
	die();
}
elseif($share->uri_action=="getsources" ) {
	$share->update_query_limit();
	$contents = $share->get_sources();

	echo $contents;
	die();
}
elseif($share->uri_action=="getfilenames" ) {
    $share->update_query_limit();
    $contents = $share->get_filenames();
    echo $contents;
    die();
}
elseif($share->uri_action=="getsourcesraw" ) {
	$share->update_query_limit();
	$contents = $share->get_sources_raw();
	die();
}
elseif($share->uri_action=="dailysum" ) {
	$share->update_query_limit();
	$contents = $share->get_sum();
	echo $contents;
	die();
}
elseif($share->uri_action=="getlimit" ) {
	$contents = $share->get_user_limit();
	echo $contents;
	die();
}
elseif($share->uri_action=="getfile") {
	$share->update_query_limit();
	$share->update_sample_count($share->uri_hash);
    $presignedUrl = $share->get_sample_url($share->uri_hash);
    header('Location: ' . $presignedUrl, true, 302);
    exit();
}
elseif($share->uri_action=="details") {
    $share->update_query_limit();
    $sample = $share->get_details_json();
    echo $sample;
    die();
}
elseif ($share->uri_action == 'hashlookup') {
    $share->update_query_limit();
    echo(json_encode($share->get_hashes(explode("\n", file_get_contents('php://input')))));
    die();
}
elseif($share->uri_action=="type") {
	$sample = $share->search_type_day();
	echo $sample;
	die();
}
elseif($share->uri_action=="gettypes") {
	$share->update_query_limit();
	$res = $share->get_types();
	echo $res;
	die();
}
elseif($share->uri_action=="search") {
	$share->update_query_limit();
	$sample = $share->sample_search(true);
	echo $sample;
	die();
} elseif ($share->uri_action == "upload") {
    if ($_FILES['upload']["size"] > 10000000) {
        http_response_code(413);
        die("Error: file too large");
    }
    foreach ($_FILES as $upload) {
        $res = $share->upload_sample($upload);
        if ($res['type'] === 'success') {
            echo('Success - ' . $res['sha256']);
            if (isset($res['message'])) {
                echo(' - ' . $res['message']);
            } else {
                $share->increment_query_limit();
            }
        } else {
            http_response_code(400);
            echo('Failed - ' . $res['message']);
        }
    }
    die();
}
elseif ($share->uri_action == 'download_url') {
    header('Content-Type: application/json');
    if (!isset($_SERVER['REQUEST_METHOD']) or ($_SERVER['REQUEST_METHOD'] !== 'POST')) {
        http_response_code(400);
        die(json_encode(array('error' => 'invalid method, only POST allowed on this endpoint')));
    }
    if (!isset($_POST['url'])) {
        http_response_code(400);
        die(json_encode(array('error' => 'missing POST field "url"')));
    }
    if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
        http_response_code(400);
        die(json_encode(array('error' => 'invalid value in field "url"')));
    }

    $url = $_POST['url'];
    $recursive = 0;
    if (isset($_POST['recursive'])) {
	    if ( strtolower($_POST['recursive']) == "true" or $_POST['recursive'] == 1) $recursive = 1;
    }
    if ($recursive && !$user->recursiveUrlDownloadAllowed) {
        http_response_code(403);
        die(json_encode(array('error' => 'not allowed to perform recursive URL downloads')));
    }
    $guid = $share->task_url_download($user->id, $url, $recursive);

    echo json_encode(array('guid' => $guid));
    exit();
}
elseif ($share->uri_action == 'download_url_check') {
    header('Content-Type: application/json');

    if (!isset($_SERVER['REQUEST_METHOD']) or ($_SERVER['REQUEST_METHOD'] !== 'GET')) {
        http_response_code(400);
        die(json_encode(array('error' => 'invalid method, only GET allowed on this endpoint')));
    }
    if (!isset($_GET['guid'])) {
        http_response_code(400);
        die(json_encode(array('error' => 'missing GET parameter "guid"')));
    }
    if (!$share->is_valid_guid($_GET['guid'])) {
        http_response_code(400);
        die(json_encode(array('error' => 'invalid value in field "guid"')));
    }
    $guid = $_GET['guid'];
    echo json_encode(array('guid' => $guid, 'status' => $share->get_download_status($user->id, $guid)));
    exit();
}
