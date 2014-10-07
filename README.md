#Git Auto Deployment
Git duto deployment using POST deploy hooks that are offered by GitHub and BitBucket.

***
## Install
* create a directory for deployment control site
```bash
mkdir /var/www/deploy
cd /var/www/deploy
```
* clone this repo
```bash
git clone git@github.com:Ciki/git-deploy.git .
```
* setup apache/nginx/other web-server site (ex. deploy.some.site) to /var/www/deploy

## Setup
* fill deploy config with your repos
```php
$repos = array(
    array(
		'name' => 'prism-code-highlighting',
        'branch' => 'master',
        'path' => '/home/usr/example/wpcopilot.net/wp-content/plugins/prism-code-highlighting/',
		'service' => Deployer::SERVICE_GITHUB,
    ),
    array(
		'name' => 'another-plugin',
        'branch' => 'deploy',
        'path' => '/home/usr/example/wpcopilot.net/wp-content/plugins/another-plugin/',
		'service' => Deployer::SERVICE_GITHUB,
        'remote' => 'bbremote',
    )
);
```
* setup GitHub/Bitbucket POST hooks
 * GitHub (https://help.github.com/articles/post-receive-hooks) to http://deploy.some.site/github.php
 * Bitbucket (https://confluence.atlassian.com/display/BITBUCKET/POST+Service+Management) to http://deploy.some.site/bitbucket.php

### Private repos
* create local ssh key
```bash
ssh-keygen -t rsa -f ~/.ssh/id_rsa -C 'Bitbucket deploy'
```
* add public key as Deploy Key to your repo

More: https://confluence.atlassian.com/pages/viewpage.action?pageId=271943168

## Usage
* commit and push