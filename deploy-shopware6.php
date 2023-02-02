<?php

declare(strict_types=1);

namespace Deployer;

set('console', 'bin/console');
set('shopware_build_path', '/tmp/build');
set('rsync', [
    'exclude' => [
        'config/jwt',
        'files/',
        'var/cache/',
        'var/log/',
        'public/media/',
        'public/sitemap',
        'public/thumbnail/',
        '.env',
        '.git',
        '.deployment/',
    ],
    'exclude-file'  => false,
    'include'       => [],
    'include-file'  => false,
    'filter'        => [],
    'filter-file'   => false,
    'filter-perdir' => false,
    'flags'         => 'rzEv',
    'options'       => [
        'delete',
        'links',
        'quiet',
    ],
    'timeout' => null,
]);

task('shopware6:plugins:install_update', function (): void {
    run('cd {{release_path}} && {{bin/php}} {{console}} plugin:refresh');

    foreach (get('plugins') as $plugin) {
        run("cd {{release_path}} && {{bin/php}} {{console}} plugin:install {$plugin} --activate");
        run("cd {{release_path}} && {{bin/php}} {{console}} plugin:update {$plugin}");
    }

    run('cd {{release_path}} && {{bin/php}} {{console}} cache:clear');
});

task('shopware6:update', function (): void { // highly experimental
    run('cd {{release_path}} && [ ! -f vendor/autoload.php ] || {{bin/php}} {{console}} system:update:prepare');
    run('cd {{release_path}} && [ ! -f vendor/autoload.php ] || {{bin/php}} {{console}} system:update:finish --skip-asset-build');
});

task('shopware6:messenger:stop', function (): void {
    if (has('previous_release')) {
        run('cd {{previous_release}} && {{bin/php}} {{console}} messenger:stop-workers');
    }
});

task('shopware6:bundle:dump', function (): void {
    run('cd {{release_path}} && {{bin/php}} {{console}} bundle:dump');
});

/** @see https://developer.shopware.com/docs/guides/hosting/installation-updates/deployments/build-w-o-db#compiling-the-storefront-without-database */
task('shopware6:theme:compile', function (): void {
    run('cd {{release_path}} && {{bin/php}} {{console}} theme:compile');
});
