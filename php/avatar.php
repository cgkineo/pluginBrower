<?php
	include "../config.php";
	include "lib/users.php";
	
	$name = $_GET['name'];
	$avatarfilename = $temporary_storage_dir . "avatar-".$name.".jpeg";

	$repo = new stdClass();
	$repo->user = $name;
	$json = array($repo);

	getUsers($json, false);

	if (!file_exists($avatarfilename)) {
		header("HTTP/1.0 404 Not Found");
	} else {
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: image/jpeg");
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
		echo file_get_contents($avatarfilename);
	}
	
?>