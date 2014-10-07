<?php
// Config
$repos = [[
	'name' => 'Digitilt',
	'branch' => 'labs',
	'remote' => 'origin',
	'path' => ROOT_DIR,
	'service' => Deployer::SERVICE_GITHUB,
	'onDeployStart' => function() {
		switchMaintenanceMode(TRUE);
	},
	'onDeployed' => function() {
		cleanCache();
		switchMaintenanceMode(FALSE);
	},
	]];


$payload = getPayload();
if (!$payload) {
	die('No payload present');
}

$service = getService();
if (!$service) {
	die('Unknown hook service!');
}

try {
	$logDir = ROOT_DIR . '/log/deploy';
	@mkdir($logDir, 0777, true);
	$logger = new Logger($logDir, NULL, true);
	$deployer = new Deployer($repos, $logger);
	$deployer->tryDeploy($payload, $service);
} catch (\RuntimeException $e) {
	$logger->log($e);
	die('Deployment error. More info in log..');
}

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


/**
 * @return string|NULL One of Deployer::SERVICE_* constants
 */
function getService()
{
	$service = NULL;
	$headers = apache_request_headers();
	// https://developer.github.com/webhooks/#delivery-headers
	if (isset($headers['User-Agent']) && strncmp($headers['User-Agent'], 'GitHub-Hookshot/', 16) === 0) {
		$service = Deployer::SERVICE_GITHUB;
		// todo: how to detect Bitbucket hook service?
	} elseif (false) {
		$service = Deployer::SERVICE_BITBUCKET;
	}
	return $service;
}

