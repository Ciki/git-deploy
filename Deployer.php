<?php

namespace Ciki\Deploy;

use RuntimeException;

/**
 * Deployer class for git deployment.
 * @see git_update.php for example usage.
 *
 * DOCS
 * ====
 * $repo object/array consists of following keys:
 *  name			The name of the repo we are attempting deployment for.
 *  branch			The name of the branch to pull from.
 *  path			The path to where your website and git repository are located, can be relative or absolute path
 *  service			The service type. One of self::SERVICE_* constants
 *  remote			[Optional] The name of the remote to pull from.
 *  onDeployStart	[Optional] A callback function to call just before the deploy start.
 *  onDeployed		[Optional] A callback function to call after the deploy has finished.
 */
class Deployer
{
	/** @type string */
	const SERVICE_GITHUB = 'github';
	const SERVICE_BITBUCKET = 'bitbucket';


	/** @var stdClass[] Registered deploy repository objects */
	private $repos = array();

	/** @var array */
	private $requiredRepoKeys = array('name', 'branch', 'path', 'service');

	/** @var ILogger */
	private $logger;


	/**
	 * @param array $repos For more info see `registerRepository` method docs.
	 * @param ILogger $logger
	 */
	public function __construct(array $repos, ILogger $logger = NULL)
	{
		foreach ($repos as $repo) {
			$this->registerRepository($repo);
		};
		if ($logger === NULL) {
			$logger = new Logger(__DIR__);
		}
		$this->logger = $logger;
	}


	/**
	 * Register repository for deployment.
	 * @param array $repo Repository config. See class doc for more info.
	 * @throws RuntimeException
	 */
	public function registerRepository(array $repo)
	{
		foreach ($this->requiredRepoKeys as $key) {
			if (empty($repo[$key])) {
				throw new RuntimeException("Required repository item '$key' is missing.");
			}
		}

		$repoKey = $this->buildRepoKey($repo['name'], $repo['branch'], $repo['service']);
		if (isset($this->repos[$repoKey])) {
			throw new RuntimeException("Repository '{$repo['name']}' is already registered!");
		}

		// check path
		$repo['path'] = realpath($repo['path']) . DIRECTORY_SEPARATOR;
		if (!file_exists($repo['path'])) {
			throw new RuntimeException("Repository ({$repo['name']}) path '{$repo['path']}' does not exist!");
		}

		$defaults = array(
			'remote' => 'origin',
			'onDeployStart' => null,
			'onDeployed' => null,
		);
		$this->repos[$repoKey] = (object) array_merge($defaults, $repo);
	}


	/**
	 * Build unique repository key.
	 * @param string $name
	 * @param string $branch
	 * @param string $service
	 * @return string
	 */
	private function buildRepoKey($name, $branch, $service)
	{
		return join('-', [
			$name,
			$branch,
			$service,
		]);
	}


	/**
	 * Try to deploy $payload using $service.
	 * @param string $payload JSON-encoded payload
	 * @param string $service One of self::SERVICE_* constants
	 * @throws RuntimeException
	 */
	public function tryDeploy($payload, $service)
	{
		$payload = json_decode($payload);
		switch ($service) {
			case self::SERVICE_GITHUB:
				$name = $payload->repository->name;
				$branch = basename($payload->ref);
				$commit = substr($payload->commits[0]->id, 0, 12);
				break;

			case self::SERVICE_BITBUCKET:
				$name = $payload->repository->name;
				$branch = $payload->commits[0]->branch;
				$commit = $payload->commits[0]->node;
				break;

			default:
				throw new RuntimeException('Unknown service!');
				break;
		}

		$repoKey = $this->buildRepoKey($name, $branch, $service);
		if (isset($this->repos[$repoKey])) {
			$repo = $this->repos[$repoKey];
			$repo->commit = $commit;
			$this->execute($repo);
		} else {
			$this->logger->log("Repository '$repoKey' not found!");
		}
	}


	/**
	 * Execute necessary commands to deploy the code.
	 * @param stdClass $repo Repository object. See class doc for more info.
	 */
	private function execute($repo)
	{
		try {
			if (is_callable($repo->onDeployStart)) {
				call_user_func($repo->onDeployStart);
			}

			// Make sure we're in the right directory
			exec('cd ' . escapeshellarg($repo->path));

			$output = [];

			// make sure we are in desired branch
			exec('git checkout ' . escapeshellarg($repo->branch), $output);

			// Discard any changes to tracked files since our last deploy
			exec('git reset --hard HEAD', $output);

			// Update the local repository
			// fetch `$repo->branch` branch from `$repo->remote` repo to currently checked out branch
			exec('git pull ' . escapeshellarg($repo->remote) . ' ' . escapeshellarg($repo->branch), $output);

			// Secure the .git directory
			echo exec('chmod -R og-rx .git');

			if (is_callable($repo->onDeployed)) {
				call_user_func($repo->onDeployed);
			}

			$this->logger->log('[SHA: ' . $repo->commit . '] Deployment of ' . $repo->name . ' from branch ' . $repo->branch . ' successful');

//			$this->logger->log($_POST, 'DEBUG');
			$this->logger->log($output, 'DEBUG');
		} catch (RuntimeException $e) {
			$this->logger->log($e, 'ERROR');
		}
	}


}