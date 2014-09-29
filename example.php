<?php
// Config
$repos = array(
	'Digitilt' => array(
		'branch' => 'labs',
		'remote' => 'origin',
		'path' => __DIR__ . '/../../../',
//		'post_deploy' => 'callback',
	),
);


// Registers all of our repos with the Deploy class
require 'inc/class.deploy.php';
foreach ($repos as $name => $repo) {
	Deploy::register_repo($name, $repo);
}


// Make sure we have a payload, stop if we do not.
if (!isset($_POST['payload'])) {
	die('<h1>No payload present</h1><p>A POST payload is required to deploy from this script.</p>');
}
$payload = $_POST['payload'];


// https://developer.github.com/webhooks/#delivery-headers
if (strncmp($_SERVER['User-Agent'], 'GitHub-Hookshot/', 16)) {
	require 'GithubDeploy.php';
	$deploy = new GitHubDeploy($payload);

// todo: how to detect Bitbucket hook service?
} elseif (false) {
	require 'BitBucketDeploy.php';
	$deploy = new BitBucketDeploy($payload);
} else {
	die('Unknown hook service!');
}



$deploy->execute();
