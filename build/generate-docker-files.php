<?php
declare(strict_types=1);

/**
 * Small script to generate custom Docker image based on php7.4-apache.
 *
 * - Switch to port 8080
 * - Add extensions for Firefly III
 * - Install localizations
 * - Add custom start & run scripts
 */

// [--platform=<platform>]

define('ROOT', $argv[1] ?? '.');
define('PHP_TO_USE', $argv[2] ?? '7.4');

$files = [
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/Dockerfile', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-entrypoint', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/apache2-foreground', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-ext-configure', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-ext-enable', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-ext-install', PHP_TO_USE),
    sprintf('https://raw.githubusercontent.com/docker-library/php/master/%s/buster/apache/docker-php-source', PHP_TO_USE),
];

foreach ($files as $file) {
    $content  = file_get_contents($file);
    $parts    = explode('/', $file);
    $filename = sprintf('%s/%s', ROOT, $parts[count($parts) - 1]);
    file_put_contents($filename, $content);
    echo sprintf("Downloaded %s\n", $filename);
}

// copy original file
copy(sprintf('%s/Dockerfile', ROOT), sprintf('%s/Dockerfile.original', ROOT));

# step: change "EXPOSE 80" from Dockerfile
$filename = sprintf('%s/Dockerfile', ROOT);
$content  = file_get_contents($filename);
$content  = str_replace("EXPOSE 80\n", "EXPOSE 8080\n", $content);
file_put_contents($filename, $content);
debugMessage('Replace port exposure.');
unset($content);

# 2021-01-09 step removed because its no longer needed. was used in old azure build
# step: replace "FROM debian:buster-slim" with something else (3 lines):
#$filename = sprintf('%s/Dockerfile', ROOT);
#$content  = file_get_contents($filename);
#$content  = str_replace("FROM debian:buster-slim\n", "ARG BASE\nFROM \${BASE} AS runtime\nARG ARCH\n", $content);
#file_put_contents($filename, $content);
#debugMessage('Replace base image.');
#unset($content);

# step: install some extra packages and remove the start command:
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
debugMessage('Append package list, remove start command.');
unset($content);

# step: inject extra code at the start of the Docker file:
$content     = file_get_contents($filename);
$injectStart = file_get_contents(sprintf('%s/docker/inject-start.txt', ROOT));
$content     = str_replace("FROM debian:buster-slim\n", sprintf("FROM debian:buster-slim\n%s", $injectStart), $content);
file_put_contents($filename, $content);
debugMessage('Add build arguments.');
unset($content);


# step: append custom commands:
$content      = file_get_contents($filename);
$extraContent = file_get_contents(sprintf('%s/docker/inject-custom.txt', ROOT));
$content      = $content . "\n" . $extraContent;
file_put_contents($filename, $content);
debugMessage('Add base code.');
unset($content);

debugMessage('Done!');


function debugMessage(string $str): void
{
    echo sprintf("%s\n", $str);
}