<div class="markdown-body">
<?php
	include "../config.php";
	include "lib/repo.php";
	
	$url = $_GET['url'];
	$filename = $temporary_storage_dir . "readme-" . preg_replace("/\//", "-", $url) . ".html";

	$modified = getReadme($url);

	if (!file_exists($filename)) {
		header("HTTP/1.0 404 Not Found");
		header("X-Refreshed: ". ($modified ? "true" : "false"));
	} else {
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: text/html");
		header("X-Refreshed: ".($modified ? "true" : "false"));
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
		echo file_get_contents($filename);
	}
	
?>
</div>