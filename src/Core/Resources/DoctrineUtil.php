<?php
namespace App\Core\Resources;

/**
 *
 * Class DoctrineUtil
 */
class DoctrineUtil
{
  const CONNECTION_VAR_NAME = "conn";
  const ENTITY_MANAGER_VAR_NAME = "entityManager";
  const CONFIG_VAR_NAME = "config";

  public static function getConnection() : array
  {
    return $GLOBALS[DoctrineUtil::CONNECTION_VAR_NAME];
  }
  public static function getEntityManager() : \Doctrine\ORM\EntityManager
  {
    return $GLOBALS[DoctrineUtil::ENTITY_MANAGER_VAR_NAME];
  }
  public static function getConfig() : \Doctrine\ORM\Configuration
  {
    return $GLOBALS[DoctrineUtil::CONFIG_VAR_NAME];
  }
}