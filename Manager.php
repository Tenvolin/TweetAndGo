<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include "DataParser.php";
include "DataFetcher.php";
include "util.php";

include "vendor/autoload.php";

// todo: batch $pagesToFetch to push to DB somehow.
$pagesToFetch = 50;

$dataFetcher = new DataFetcher();

$dataFetcher->fetch($pagesToFetch);
