<?php

namespace Deployer;

require_once 'recipe/common.php';

set('repository', 'https://github.com/wushu06/magento2-sample.git');

set('branch', 'feature/v2.4');

set('deploy_path', '/var/www/html');

set('default_timeout', 900);

set('theme_path', '');

set('composer_options', 'install --verbose --prefer-dist --no-progress --no-interaction --no-dev');

// Configuration
set('shared_files', [
    'app/etc/env.php',
    'sitemap.xml'
]);
set('shared_dirs', [
    'var/log',
    'var/backups',
    'var/session',
    'var/report',
    'pub/media'
]);
set('writable_dirs', [
    'var',
    'pub/static',
    'pub/media'
]);

set('clear_paths', [
    'generated/*'
]);

set('npm', function () {
    return locateBinaryPath('npm');
});

// Tasks
desc('Enable all modules');
task('magento:enable', function () {
    run("{{bin/php}} {{release_path}}/bin/magento module:enable --all");
});

desc('Setup Magento');
task('magento:setup', function () {
    run('{{bin/php}} {{release_path}}/bin/magento setup:install \
        --base-url="http://192.168.1.23/" \
        --db-host="localhost" \
        --db-name="magento" \
        --db-user="root" \
        --db-password="Test1234."  \
        --admin-firstname="Nour" \
        --admin-lastname="Latreche" \
        --admin-email="nour@elementarydigital.co.uk" \
        --admin-user="ed_admin" \
        --admin-password="Test1234" \
        --language="en_GB" \
        --currency="GBP" \
        --timezone="Europe/London" \
        --session-save=redis \
        --session-save-redis-host=127.0.0.1 \
        --session-save-redis-log-level=4 \
        --session-save-redis-db=2 \
        --use-rewrites="1" \
        --backend-frontname="admin" \
        --search-engine=elasticsearch7 \
        --elasticsearch-host=127.0.0.1 \
        --elasticsearch-port=9200'
    );
});

desc('Compile magento di');
task('magento:compile', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:di:compile");
});

desc('Gulp task runner');
task('magento:gulp', function () {
    run("cd {{release_path}} && {{npm}} install && gulp");
});

desc('Gulp minify js');
task('magento:gulp:minify', function () {
    run("cd {{release_path}} && gulp minify");
});

desc('Gulp cleanup');
task('magento:gulp:cleanup', function () {
    run("cd {{release_path}} && rm -r {{release_path}}/node_modules");
});

desc('Copy auth.json file in-place');
task('composer:auth', function () {
    run("test -f {{deploy_path}}/shared/auth.json && cp {{deploy_path}}/shared/auth.json {{release_path}}/auth.json");
});

desc('Removes auth.json file');
task('composer:auth:remove', function () {
    run("test -f {{release_path}}/auth.json && rm {{release_path}}/auth.json");
    run("test -f {{release_path}}/app/etc/env.php && rm {{release_path}}/app/etc/env.php");
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    //run("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy --theme {{theme_path}} en_GB en_US");
    run("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy en_GB en_US");
});

desc('Deploy backend assets');
task('magento:deploy:assets-backend', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy --area adminhtml en_GB en_US");
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d (echo {{deploy_path}}/current) ]; then {{bin/php}} {{deploy_path}}/current/bin/magento maintenance:enable; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d (echo {{deploy_path}}/current) ]; then {{bin/php}} {{deploy_path}}/current/bin/magento maintenance:disable; fi");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:upgrade");
});

desc('Set production mode');
task('magento:mode', function () {
    run("{{bin/php}} {{release_path}}/bin/magento deploy:mode:set production --skip-compilation");
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{release_path}}/bin/magento cache:flush");
});

desc('Copy robots.txt File');
task('deploy:robots', function () {
    run("cp {{deploy_path}}/robots.txt {{release_path}}/robots.txt");
});

desc('Copy .htaccess File');
task('deploy:htaccess', function () {
    run("rm {{release_path}}/.htaccess && cp {{deploy_path}}/htaccess {{release_path}}/.htaccess");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:setup',
    // 'magento:maintenance:enable',
    'magento:upgrade:db',
    'magento:mode',
    // 'magento:maintenance:disable',
    'magento:compile',
    'magento:deploy:assets',
    'magento:deploy:assets-backend'
]);

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'composer:auth',
    'deploy:vendors',
    'deploy:clear_paths',
    'composer:auth:remove',
    'deploy:magento',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

//after('deploy:failed', 'magento:maintenance:disable');
