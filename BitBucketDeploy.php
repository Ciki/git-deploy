<?php
require_once __DIR__ . '/Deploy.php';

/**
 * Deploys BitBucket git repos
 */
class BitBucketDeploy extends Deploy
{

	/**
	 * Decodes and validates the data from bitbucket and calls the
	 * deploy constructor to deploy the new code.
	 *
	 * @param string $payload The JSON encoded payload data.
	 */
	function __construct($payload)
	{
		$payload = json_decode($payload);
		$name = $payload->repository->name;
		$branch = $payload->commits[0]->branch;
		if (isset(parent::$repos[$name]) && parent::$repos[$name]['branch'] === $branch) {
			$data = parent::$repos[$name];
			$data['commit'] = $payload->commits[0]->node;
			parent::__construct($name, $data);
		}
	}


}