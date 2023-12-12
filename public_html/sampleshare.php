<?php

/* ****************************************** */
/* Norman SampleShare Client Framework        */
/* Version 1.30                               */
/* Created by Trygve Brox - Norman ASA - 2010 */
/* ****************************************** */
/* Modified by Silas Cutler for Malshare.com  */
/* Version 1.0                                */
/*                                            */
/* ****************************************** */


include("server_includes.php");

$share = new ServerObject();
if($share->uri_action=="") die();

$user = new UserObject($share->sql, $share->uri_api_key);

if($share->uri_action=="getlist" ) {		
	$contents = $share->get_list();
	die();
}

if($share->uri_action=="getlistraw" ) {
        $contents = $share->get_list_raw();
        die();
}

if($share->uri_action=="getsources" ) {
        $contents = $share->get_sources();
        die();
}

if($share->uri_action=="getsourcesraw" ) {
        $contents = $share->get_sources_raw();
        die();
}

if($share->uri_action=="dailysum" ) {
        $contents = $share->get_sum();
	echo $contents;
        die();
}


if($share->uri_action=="getfile") {
	$hash = $share->uri_hash;
	$sample = $share->get_sample_url($hash);
	$share->update_query_limit();
    $presignedUrl = $share->get_sample_url($share->uri_hash);
    header('Location: ' . $presignedUrl, true, 302);
    exit();
}
if($share->uri_action=="details") {
        $hash = $share->uri_hash;
	$sample = $share->get_details_json($hash);
	echo $sample;
        die();
}

if($share->uri_action=="type") {
        $sample = $share->search_type_day();
        echo $sample;
        die();
}

?>

