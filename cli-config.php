<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-24
 * Time: 3:24 AM
 */
require_once "config/bootstrap.php";
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
