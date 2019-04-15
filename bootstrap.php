<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-24
 * Time: 3:43 AM
 */
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "vendor/autoload.php";

// Perform ORM ops to insert messages, and then refactor out a class.
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . "/src/model/"), $isDevMode);

// database configuration parameters
// TODO: Definitely needs to be cleaned up for deployment. Password should not be available, etc.
$conn = array(
  'driver' => 'pdo_mysql',
  'user' => 'tenvolin',
  'password' => 'test',
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