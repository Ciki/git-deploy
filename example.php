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
require __DIR__ . '/Deploy.php';
foreach ($repos as $name => $repo) {
	Deploy::register_repo($name, $repo);
}


// Make sure we have a payload, stop if we do not.
$payload = getPayload();
if (!$payload) {
	die('<h1>No payload present</h1>');
}


// https://developer.github.com/webhooks/#delivery-headers
if (strncmp($_SERVER['User-Agent'], 'GitHub-Hookshot/', 16)) {
	require __DIR__ . '/GithubDeploy.php';
	$deploy = new GitHubDeploy($payload);
// todo: how to detect Bitbucket hook service?
} elseif (false) {
	require __DIR__ . '/BitBucketDeploy.php';
	$deploy = new BitBucketDeploy($payload);
} else {
	die('Unknown hook service!');
}


$deploy->execute();

/**
 * @return string|NULL JSON encoded payload
 */
function getPayload()
{
	if (isset($_POST['payload'])) {
		$json = $_POST['payload'];
	} else {
		$json = file_get_contents('php://input');
	}
	return $json ? : NULL;
}

