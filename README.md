# Shopware 6 deployment
This repository contains a [deployer](https://deployer.org/) configuration for Shopware 6.

## Usage
Copy the entire `.deployment` directory from the `example` directory of this repository into your project and modify it to suit your needs.

Use [`.gitlab-ci.yml`](./example/.gitlab-ci.yml) as an example and/or adapt it to your ci syntax.

### Assumptions
* We assume that the `js` files will be built in CI and published to the deployment jobs via artifacts.
* We assume that your shop is prepared for compilation without a database in CI. For more information, see [here](https://developer.shopware.com/docs/guides/hosting/installation-updates/deployments/build-w-o-db#compiling-the-storefront-without-database).
* We assume that all plugins are required via composer (custom plugins are placed in `custom/static-plugins`).

## Configuration
### `inventory.yml`
#### Required adjustments
* `cachetool` => Needs to be the path of your php socket - if you want to use `cachetool:clear:opcache` in the deployment steps
* `deploy_path` => The path where the deployment should be setup on the server
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

* `deployer` will create several directories in your deployment path on the server:
  * `.dep`: Information about the releases
  * `current`: Symlink that will always be linked to the latest release. Use it for your document root.
  * `releases`: This directory contains the last 5 releases. If you want to keep more or less releases, simply overwrite the `keep_releases` setting as stated above.
  * `shared/`: Here you can add additional files that will persist between deployments (like the already shared `.env` or `.htaccess`)

## Contribution
Feel free to send pull requests if you have any optimizations. They will be highly appreciated.

## License
MIT licensed, see `LICENSE`
