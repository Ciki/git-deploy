<?php
//die('git update is currently disabled.');

/**
 * Automatic deployment from GitHub/BitBucket
 * ==========================================
 *
 * 1. First clone git repo from GitHub via ssh, e.g.
 * ```
 *  #check out `labs` branch but download all branches /whole repo/
 *  git clone -b labs git@github.com:Maga-Design-Group/Digitilt.git`
 *
 * 	# With Git 1.7.10 and later, add --single-branch to prevent fetching of all branches. http://stackoverflow.com/a/4568323/848166
 *  git clone -b labs git@github.com:Maga-Design-Group/Digitilt.git --single-branch
 * ```
 *
 * 2. Authenticate server with GitHub as known host https://help.github.com/articles/set-up-git#next-steps-authenticating-with-github-from-git
 *
 * 3. In GitHub repo 'Settings/Webhooks & Services' create new webhook for push event pointing to this file
 *
 * 4. Finally for every push event run sth like
 * ```
 *  cd $projectRootDir
 *  git checkout $branch -- make sure we are in desired branch
 *  git reset --hard HEAD
 *  git pull origin $branch -- fetch desired branch from origin remote repo to currently checked out branch
 * ```
 * which is subject of this file & Deployer::execute() respectively
 *
 * 5. YOU'RE DONE!
 */
use Tracy\Debugger;
use Ciki\Deploy\Deployer;
use Ciki\Deploy\Logger;

require __DIR__ . '/constants.inc.php';
if (!@include LIBS_DIR . '/Nette/loader.php') {
	require LIBS_DIR . '/minified/Nette/loader.php';
}

setupRobotLoader();
Debugger::enable('213.215.67.146', __DIR__ . '/../log', 'matula.m@gmail.com');
//Debugger::enable(FALSE, __DIR__ . '/../log', 'matula.m@gmail.com');


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

/**
 * Switch maintenance mode.
 * @param bool $switch
 * @throws InvalidArgumentException
 */
function switchMaintenanceMode($switch)
{
	if (!is_bool($switch)) {
		throw new InvalidArgumentException('Argument $switch must be bool!');
	}
	$indexPath = __DIR__ . '/index.php';
	$tmpIndexPath = $indexPath . '.tmp';
	$maintenancePath = __DIR__ . '/.maintenance.php';
	if ($switch) {
		copy($indexPath, $tmpIndexPath);
		copy($maintenancePath, $indexPath);
	} else {
		rename($tmpIndexPath, $indexPath);
	}
}


function cleanCache()
{
	Utils\FileSystem::cleanDir(WEBTEMP_DIR);
	Utils\FileSystem::cleanDir(TEMP_DIR . '/cache');
}


/**
 * DO NOT CHANGE ANYTHING BELOW
 */
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

function setupRobotLoader()
{
	$loader = new Nette\Loaders\RobotLoader;
	$loader->addDirectory(LIBS_DIR);
	$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(TEMP_DIR . '/cache'));
	$loader->register();
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

