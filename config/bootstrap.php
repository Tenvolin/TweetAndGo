<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (is_array($env = @include dirname(__DIR__).'/.env.local.php')) {
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_ENV[$k] ?? (isset($_SERVER[$k]) && 0 !== strpos($k, 'HTTP_') ? $_SERVER[$k] : $v);
    }
} elseif (!class_exists(Dotenv::class)) {
    throw new RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} else {
    // load all the .env files
    (new Dotenv(false))->loadEnv(dirname(__DIR__).'/.env');
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

// Perform ORM ops to insert messages, and then refactor out a class.
$isDevMode = true;
$root = 'E:\DevPlayground\Projects\php\TweetServer\\'; // TODO: Set constants properly somewhere in a config file.
$config = Setup::createAnnotationMetadataConfiguration(array(dirname(__DIR__) . '\\src\\Entity\\'), $isDevMode);

// database configuration parameters
// TODO: Definitely needs to be cleaned up for deployment. Password should not be available, etc.
$conn = array(
  'driver' => 'pdo_mysql',
  'user' => 'tenvolin',
  'password' => 'Ac123123!',
  'dbname' => 'simple_archiver',
  'host' => 'localhost',
  'port' => '3306',
  'charset' => 'utf8mb4',
  'driverOptions' => array(
    1002 => 'SET NAMES utf8mb4'
  )
);

//$entityManager = EntityManager::create($conn, $config);
// obtaining the entity manager
try {
  $entityManager = EntityManager::create($conn, $config);
} catch (Exception $e) {
  print("EntityManager instantiation error: $e\n");
}

