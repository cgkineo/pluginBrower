<?php

	function getUsers($json, $norefreshes) {
		global $temporary_storage_dir, $github_access_token, $git_api_users_url, $git_protocol_url;

		$context = stream_context_create(array("http" => array("header" => "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36\r\nAuthorization: token ".$github_access_token)));

		$users = array();

		$modified = false;

		$packagejson = null;
		if (!$norefreshes) {
			$packagefilename = $temporary_storage_dir . "packages.json";

			if (!file_exists($packagefilename)) {
				header("HTTP/1.0 404 Not Found");
				header("X-Error: No package file");
				exit;
			}
			$packagejson = json_decode(file_get_contents($packagefilename));
		}

		foreach($json as $repo) {
			if (!isset($repo->user)) {
				$url = preg_replace("/git:\/\//", "https://", $repo->url);
				$truncated = preg_replace("/".preg_quote($git_protocol_url, "/")."/","", $url);
				$nextslash = strpos($truncated, "/");
				$user = substr($truncated, 0, $nextslash);
				$repo->user = $user;
			} else {
				$user = $repo->user;
			}
			if (!$norefreshes) {
				$userhasrepos = false;
				foreach($packagejson as $item) {
					if ($item->user == $user) {
						$userhasrepos = true;
						break;
					}
				}
				if (!$userhasrepos) {
					header("HTTP/1.0 404 Not Found");
					exit;
				}
			}
			$users[$user] = true;
		}


		foreach ($users as $name => $value) {

			$filename = $temporary_storage_dir . "user-".$name.".json";
			$avatarfilename = $temporary_storage_dir . "avatar-".$name.".jpeg";
			$userdata = "";

			if (!file_exists($filename)) {
				$url = $git_api_users_url.$name;
				$userdata = file_get_contents($url, false, $context);
				file_put_contents($filename, $userdata);
				$modified = true;
			} else {
				$filemtime = filemtime($filename);
				$filemexpiry = time()  - rand(43200, 86400);
				if (($filemtime < $filemexpiry || filesize($filename) == 0) && !$norefreshes) {
					$url = $git_api_users_url.$name;
					$userdata = file_get_contents($url, false, $context);
					file_put_contents($filename, $userdata);
					$modified = true;
				} else {
					$userdata = file_get_contents($filename);
				}
			}

			$user = json_decode($userdata);

			$users[$name] = $user;

			if (!file_exists($avatarfilename)) {
				file_put_contents($avatarfilename, file_get_contents($user->avatar_url));
				$modified = true;
			}

		}

		return $json;


	}



?>
