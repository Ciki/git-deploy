<?php
require_once __DIR__ . '/Deploy.php';

/**
 * Deploys GitHub git repos
 */
class GitHubDeploy extends Deploy
{

	/**
	 * Decodes and validates the data from github and calls the
	 * deploy constructor to deploy the new code.
	 *
	 * @param string $payload The JSON encoded payload data.
	 */
	function __construct($payload)
	{
		$payload = json_decode($payload);
		$name = $payload->repository->name;
		$branch = basename($payload->ref);
		$commit = substr($payload->commits[0]->id, 0, 12);
		if (isset(parent::$repos[$name]) && parent::$repos[$name]['branch'] === $branch) {
			$data = parent::$repos[$name];
			$data['commit'] = $commit;
			parent::__construct($name, $data);
		}
	}


}