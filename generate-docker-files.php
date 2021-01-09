<?php
declare(strict_types=1);

/**
 * Small script to generate custom Docker image based on php7.4-apache or some other base image (for future use).
 * 
 * Apache image:
 * 
 * - Switch to port 8080
 * - Add extensions for Firefly III
 * - Install localizations
 * - Add custom start & run scripts
 */

// two options:
// 1) apache base image (apache-image) or fpm base image (fpm-image)
// 2) php 7.4 or php 8.0

define('IMG_BUILD', $argv[1] ?? 'apache-image');
define('BUILD_DIR', sprintf('%s/%s', __DIR__, IMG_BUILD));
define('PHP_TO_USE', $argv[2] ?? '7.4');

$files = [
    'apache-image' => [
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/Dockerfile', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-entrypoint', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/apache2-foreground', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-ext-configure', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-ext-enable', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-ext-install', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-source', PHP_TO_USE),
    ]
];

if(! array_key_exists(IMG_BUILD, $files)) {
    debugMessage(sprintf('Image %s cannot be build (yet).', IMG_BUILD));
    exit(1);
}

debugMessage(sprintf('Going to build %s/%s', IMG_BUILD, PHP_TO_USE));

if(!file_exists(BUILD_DIR)) {
    mkdir(BUILD_DIR, 0777, true);
}

foreach ($files[IMG_BUILD] as $file) {
    $content  = file_get_contents($file);
    $parts    = explode('/', $file);
    $filename = sprintf('%s/%s', BUILD_DIR, $parts[count($parts) - 1]);
    file_put_contents($filename, $content);
    debugMessage(sprintf('Downloaded %s', $filename));

    // if not Dockerfile, make executable
    if('Dockerfile' !== substr($file,-10)) {
        exec(sprintf('chmod +x %s', escapeshellarg($filename)));
        debugMessage(sprintf('Made executable %s', $filename));
    }
}

// copy original file
copy(sprintf('%s/Dockerfile', BUILD_DIR), sprintf('%s/Dockerfile.original', BUILD_DIR));

// the steps below depend on what image is built. 


if('apache-image' === IMG_BUILD) {
    debugMessage('Step: change "EXPOSE 80" from Dockerfile');

    $filename = sprintf('%s/Dockerfile', BUILD_DIR);
    $content  = file_get_contents($filename);
    $content  = str_replace("EXPOSE 80\n", "EXPOSE 8080\n", $content);
    file_put_contents($filename, $content);
    debugMessage('Replaced port exposure.');
    unset($content);
}


if('apache-image' === IMG_BUILD) {
    debugMessage('Step: install some extra packages and remove the start command');

    $filename = sprintf('%s/Dockerfile', BUILD_DIR);
    $content  = file_get_contents($filename);
    $packages         = ['locales', 'unzip', 'xz-utils', 'nano', 'git'];
    $packages         = array_map(function (string $string) {
        return sprintf("\t\t%s \\\n", $string);

    }, $packages);
    $packagesString   = join('', $packages);
    $searchAndReplace = [
        "# persistent / runtime deps\n"  => "# persistent / runtime deps\n# ADDED EXTRA PACKAGES\n",
        "\t\txz-utils \\\n"              => $packagesString,
        "CMD [\"apache2-foreground\"]\n" => '',
    ];

    $search  = array_keys($searchAndReplace);
    $replace = array_values($searchAndReplace);
    $content = file_get_contents($filename);
    $content = str_replace($search, $replace, $content);
    file_put_contents($filename, $content);
    debugMessage('Appended package list, removed start command.');
    unset($content);
}

if('apache-image' === IMG_BUILD) {
    debugMessage('Step: inject extra code at the start of the Docker file');

    $filename    = sprintf('%s/Dockerfile', BUILD_DIR);
    $content     = file_get_contents($filename);
    $injectStart = file_get_contents(sprintf('%s/%s-config/inject-start.txt', __DIR__, IMG_BUILD));
    $content     = str_replace("FROM debian:buster-slim\n", sprintf("FROM debian:buster-slim\n%s", $injectStart), $content);
    file_put_contents($filename, $content);
    debugMessage('Added build arguments.');
    unset($content);
}


if('apache-image' === IMG_BUILD) {
    debugMessage('Step: append custom commands');

    $filename     = sprintf('%s/Dockerfile', BUILD_DIR);
    $content      = file_get_contents($filename);
    $extraContent = file_get_contents(sprintf('%s/%s-config/inject-custom.txt', __DIR__, IMG_BUILD));
    $content      = $content . "\n" . $extraContent;
    file_put_contents($filename, $content);
    debugMessage('Added base code.');
    unset($content);
}

debugMessage(sprintf('Done building %s/%s', IMG_BUILD, PHP_TO_USE));


function debugMessage(string $str): void
{
    echo sprintf("%s\n", $str);
}