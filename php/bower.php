<?php
	include "../config.php";
	include "lib/repo.php";

	$url = $_GET['url'];
	$filename = $temporary_storage_dir . "bower-" . preg_replace("/\//", "-", $url) . ".json";

	getBower($url, false);
	

	if (!file_exists($filename)) {
		header("HTTP/1.0 404 Not Found");
	} else {
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: text/json");
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
		echo file_get_contents($filename);
	}
	
?>