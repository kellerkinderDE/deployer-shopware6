<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'recipe/composer.php';
require 'recipe/cachetool.php';
require 'deploy-shopware6.php';

// Deployer specific
set('application', 'myApplicationName');
set('bin/php', '/usr/bin/php');
set('writable_mode', 'chmod');
add('shared_files', [
    '.env',
    'public/.htaccess',
    'install.lock',
]);
add('executable_files', ['bin/console']);
add('shared_dirs', [
    'config/jwt',
    'files',
    'var/log',
    'public/media',
    'public/sitemap',
    'public/thumbnail',
]);
add('create_shared_dirs', [
    'config/jwt',
    'files',
    'var/cache',
    'var/log',
    'public/media',
    'public/sitemap',
    'public/thumbnail',
]);
add('writable_dirs', [
    'var/cache',
    'var/log',
    'files',
    'public',
]);
set('allow_anonymous_stats', false);
set('ssh_multiplexing', false);
set('timing', new \DateTime());
set('default_timeout', 600);
set('keep_releases', 5);

// Shopware / deployment specific
// source => target -- Will copy the source into the target during build
set('source_directory', '../');

set('plugins', [
    'PluginName',
    'AnotherPluginName'
]);

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'shopware6:plugins:install_update',
    'shopware6:update',
    'shopware6:bundle:dump',
    'shopware6:theme:compile',
    'deploy:symlink',
    'shopware6:messenger:stop',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->desc('Deploy your project');

after('deploy:failed', 'deploy:unlock');

inventory('inventory.yml');
