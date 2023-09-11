<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require \dirname(__DIR__).'/vendor/autoload.php';

if (\file_exists(\dirname(__DIR__).'/config/bootstrap.php')) {
    require \dirname(__DIR__).'/config/bootstrap.php';
} elseif (\method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(\dirname(__DIR__).'/.env');
}

$filesystem = new \Symfony\Component\Filesystem\Filesystem();

// ensure a fresh cache when debug mode is disabled
$filesystem->remove(__DIR__.'/../var/cache/test');

$filesystem->remove(__DIR__.'/../var/test-data');

if ($_SERVER['APP_DEBUG']) {
    \umask(0000);
}
