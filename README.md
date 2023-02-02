# Shopware 6 deployment
This repository contains a [deployer](https://deployer.org/) configuration for Shopware 6.

## Usage
Copy the contents of this repository to a folder inside your project.

Enter the following to your `.gitlab-ci.yml` or adjust it to your ci syntax.
You have to place the files in a folder called `.deployment`.

### Assumptions
* We assume that the `js` files have already been built in the CI and published to the deployment jobs via artifacts.
* We assume in the CI that the theme is already compiled. For more information, see [here] (https://developer.shopware.com/docs/guides/hosting/installation-updates/deployments/build-w-o-db#compiling-the-storefront-without-database).
* We assume that any plugin is required via the composer and/or placed in `static/plugins`.
```yaml
variables:
  DOCKER_DRIVER: overlay2
  CI: "1" # use `bin/ci` instead of `bin/console`
  SHOPWARE_SKIP_THEME_COMPILE: "true" # needed to build the js files inside the ci
  PUPPETEER_SKIP_CHROMIUM_DOWNLOAD: "true" # needed to build the js files inside the ci
  MY_DEPLOYMENT_IP: "127.0.0.1" # adjust according to your needs - if your IPs of the stage and live system differ - you might want to enter the IP directly for each CI job

.deployment: &deployment
  image: "kellerkinder/pipeline-image:8.1"
  stage: deploy
  before_script:
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add - # here we add our private key to the ssh config
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh

staging-deploy:
  <<: *deployment
  only:
    refs:
      - staging
  script:
    - ssh-keyscan -H $MY_DEPLOYMENT_IP >> ~/.ssh/known_hosts # you may want to use a CI variable for the host key and write that to known_hosts
    - cd ${CI_PROJECT_DIR}/.deployment && composer install -no
    - php vendor/bin/dep deploy staging -vvv

deploy-production:
  <<: *deployment
  only:
    refs:
      - main # or master, depending on your repository
  script:
    - ssh-keyscan -H $MY_DEPLOYMENT_IP >> ~/.ssh/known_hosts # you may want to use a CI variable for the host key and write that to known_hosts
    - cd ${CI_PROJECT_DIR}/.deployment && composer install -no
    - php vendor/bin/dep deploy production -vvv
```

## Configuration
### `inventory.yml`
#### Required adjustments
* `cachetool` => Needs to be the path of your php socket - if you want to use `cachetool:clear:opcache` in the deployment steps
* `deploy_path` => Needs to be the path of your webroot
* `user` & `hostname` should be changed according to your data

#### Optional adjustments
* `app_url` => Should be the main domain/url of your shop
* `branch` => Should be the target branch which is used for the deployment step
* `stage` => Should be the specific stage of the deployment
* `port` => If your ssh port is not `22` you have to adjust this

### `deploy.php`
#### Required adjustments
* `plugins` => Should contain a list of plugins which are automatically installed and activated (eg. managed by deployment)
* `source_directory` => Needs to be the path of your project root on the server

#### Optional adjustments
* `keep_releases` => Defines the number of previous releases which are kept on the server
* `application` => Change this value to your project name
* `rsync` => You might want to adjust the ignored files according to your project setup and file structure

## Specific tasks of our deployment file
* `shopware6:plugins:install_update`
  * Handles the installation, update and activation of plugins defined in the `plugins` section of the `deploy.php`
* `shopware6:update`
  * Executes the `system:update:prepare` and `system:update:finish` command of Shopware
* `shopware6:messenger:stop`
  * Executes `messenger:stop-workers` to reset workers
    * This requires the workers to be started automatically, e.g. via `supervisord`
* `shopware6:bundle:dump`
  * Executes `bundle:dump` and is needed to publish the prebuild `js` files 
* `shopware6:theme:compile`
  * Will execute the `theme:compile` command
  * Can be used if the theme compilation doesn't happen during the `ci` process

### Additional notes
* You should compile your js/scss files during your ci runtime
* Composer installs/updates/requirements should be done before the deployment starts

* Directories on the server within your deployment path `deployer` will create several directories:
  * `.dep`: Information about the releases
  * `current`: Symlink that will always be linked to the latest release.
  * `releases`: This directory contains the last 5 releases. If you want to keep more or less releases, simply overwrite the `keep_releases` setting as stated above.
  * `shared/`: Here you can add additional files that will persist between deployments (like the already shared `.env` or `.htaccess`)

## Contribution
Feel free to send pull requests if you have any optimizations. They will be highly appreciated.

## License
MIT licensed, see `LICENSE`