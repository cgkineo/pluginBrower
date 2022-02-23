<?php
	include "../config.php";
	include "lib/users.php";
	include "lib/repo.php";

	$filename = $temporary_storage_dir . "packages.json";

	header("Access-Control-Allow-Origin: *");
	header("Content-Type: text/json");
	header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));

	$data = "";

	$fileexists = file_exists($filename);

	if (!$fileexists || filesize($filename) == 0) {
		$data = file_get_contents($adapt_repo_url);
		$json = json_decode($data);
		$packages = getUsers($json, true);
		$packages = mergeRepoData($packages, true);
		$data = json_encode($packages, JSON_PRETTY_PRINT);
		file_put_contents($filename, $data);
		header("X-Refreshed: true");
	} else {
		$filemtime = filemtime($filename);
		$filemexpiry = time() - 1800;
		if ($filemtime < $filemexpiry || filesize($filename) == 0) {
			$data = file_get_contents($adapt_repo_url);
			$json = json_decode($data);
			$packages = getUsers($json, true);
			$packages = mergeRepoData($packages, true);
			$data = json_encode($packages, JSON_PRETTY_PRINT);
			file_put_contents($filename, $data);
			header("X-Refreshed: true");
		} else {
			$data = file_get_contents($filename);
			header("X-Refreshed: false");
		}
	}

	echo $data;


?>
