<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include_once "../bootstrap.php";
include_once "DataParser.php";
include_once "DataFetcher.php";
include_once "util.php";
include_once "model/Tweet.php";
include_once "ParseException.php";
//require_once "../vendor/autoload.php";
// todo: batch $pagesToFetch to push to DB somehow.

// string manipulation config.
mb_internal_encoding("UTF-8");
$pagesToFetch = 1;
$dataFetcher = new DataFetcher();

$accountTweets = $dataFetcher->fetchAndParse($pagesToFetch); // array of array
DataPusher::push($accountTweets, $entityManager);

