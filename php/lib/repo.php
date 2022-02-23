<?php 
	require_once 'MarkdownExtra.inc.php';

	use \Michelf\MarkdownExtra;

	function getReadme($url) {

		global $temporary_storage_dir, $raw_git_url;

		$packagefilename = $temporary_storage_dir . "packages.json";
		if (!file_exists($packagefilename)) {
			header("HTTP/1.0 404 Not Found");
			exit;
		}
		
		$packagejson = json_decode(file_get_contents($packagefilename));
		
		$packagehasurl = false;
		foreach($packagejson as $item) {
			if ($item->giturl == $url) {
				$packagehasurl = true;
				break;
			}
		}
		if (!$packagehasurl) {
			header("HTTP/1.0 404 Not Found");
			exit;
		}

		$repo = $raw_git_url . $url . "/master/README.md";
		$repoAlt = $raw_git_url . $url . "/develop/README.md";
		$filename = $temporary_storage_dir . "readme-" . preg_replace("/\//", "-", $url) . ".html";

		$data = "";
		$file = "";

		$fileexists = file_exists($filename);

		$modified = false;

		getBower($url, false);

		if (!$fileexists || filesize($filename) == 0) {
			$file = file_get_contents($repo);
			if (strlen($file) == 0 ) {
				$file = file_get_contents($repoAlt);
			}
			$data = MarkdownExtra::defaultTransform($file);
			$data = parseGitHubImages($data);
			file_put_contents($filename, $data);
			$modified = true;
		} else {
			$filemtime = filemtime($filename);
			$filemexpiry = time() - rand(43200, 86400);
			if ($filemtime < $filemexpiry || filesize($filename) == 0) {
				$file = file_get_contents($repo);
				if (strlen($file) == 0) {
					$file = file_get_contents($repoAlt);
				}
				$data = MarkdownExtra::defaultTransform($file);
				$data = parseGitHubImages($data);
				file_put_contents($filename, $data);
				$modified = true;
			} else {
				$data = file_get_contents($filename);
			}
		}

		return $modified;

	}

	function getBower($url, $norefreshes) {

		global $temporary_storage_dir, $raw_git_url;

		if (!$norefreshes) {
			$packagefilename = $temporary_storage_dir . "packages.json";
			if (!file_exists($packagefilename)) {
				header("HTTP/1.0 404 Not Found");
				exit;
			}
			
			$packagejson = json_decode(file_get_contents($packagefilename));
			
			$packagehasurl = false;
			foreach($packagejson as $item) {
				if ($item->giturl == $url) {
					$packagehasurl = true;
					break;
				}
			}
			if (!$packagehasurl) {
				header("HTTP/1.0 404 Not Found");
				exit;
			}
		}
		
		$bower = $raw_git_url . $url . "/master/bower.json";
		$bowerAlt = $raw_git_url . $url . "/develop/bower.json";
		$filename = $temporary_storage_dir . "bower-" . preg_replace("/\//", "-", $url) . ".json";

		$data = "";
		$file = "";

		$fileexists = file_exists($filename);

		$modified = false;

		if (!$fileexists || filesize($filename) == 0) {
			$fileBower = file_get_contents($bower);
			if (strlen($fileBower) == 0) {
				$fileBower = file_get_contents($bowerAlt);
			}
			file_put_contents($filename, $fileBower );
			$modified = true;
		} else {
			$filemtime = filemtime($filename);
			$filemexpiry = time()  - rand(43200, 86400);
			if (($filemtime < $filemexpiry || filesize($filename) == 0) && !$norefreshes) {
				$fileBower = file_get_contents($bower);
				if (strlen($fileBower) == 0) {
					$fileBower = file_get_contents($bowerAlt);
				}
				file_put_contents($filename, $fileBower );
				$modified = true;
			} else {
				$data = file_get_contents($filename);
			}
		}

		return $modified;
	}

	function mergeRepoData($packages, $norefreshes) {
		global $temporary_storage_dir, $git_url;

		foreach($packages as $package) {
			$url = "";
			if (substr($package->url, -4) == ".git") {
				$url = substr($package->url, strlen($git_url), -4);
			} else {
				$url = substr($package->url, strlen($git_url));
			}
			
			getBower($url, $norefreshes);
			$filenameBower = $temporary_storage_dir . "bower-" . preg_replace("/\//", "-", $url) . ".json";
			$bower = json_decode(file_get_contents($filenameBower));
			$package->bowername = $package->name;
			$package->type = "unknown type";
			if (isset($bower->keywords)) {
				if (in_array("adapt-component", $bower->keywords)) $package->type = "component";
				else if (in_array("adapt-extension", $bower->keywords)) $package->type = "extension";
				else if (in_array("adapt-theme", $bower->keywords)) $package->type = "theme";
				else if (in_array("adapt-menu", $bower->keywords)) $package->type = "menu";
			}
			foreach ($bower as $key => $value) {
				$package->$key = $value;
			}
			$package->giturl = $url;
		}

		return $packages;
	}

	function parseGitHubImages($text) {
		global $raw_git_url, $git_url;

		$text = preg_replace("/<img src=\"" . preg_quote($git_url, "/")."/", "<img src=\"".$raw_git_url, $text);
		$text = preg_replace("/\/blob\//", "/", $text);
		$text = preg_replace("/\/raw\//", "/", $text);

		return $text;

	}

?>