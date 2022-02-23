<?php
	include "githubaccesstoken.php";
	error_reporting(0);

	// $github_access_token is required and defined in githubaccesstoken.php
	$adapt_repo_url = "http://adapt-bower-repository.herokuapp.com/packages";
	$temporary_storage_dir = sys_get_temp_dir() . "/adapt-repo-browser/";
	$raw_git_url = "https://raw.githubusercontent.com/";
	$git_url = "https://github.com/";
	$git_protocol_url = "https://github.com/";
	$git_api_users_url = "https://api.github.com/users/";

	if (!file_exists($temporary_storage_dir)) mkdir($temporary_storage_dir);
?>
