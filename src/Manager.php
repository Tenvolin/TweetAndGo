<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include_once "../bootstrap.php";
include_once "DataFetcher.php";
include_once "DataParser.php";
include_once "DataPusher.php";
include_once "Util.php";
include_once "model/Tweet.php";
include_once "ParseException.php";
// todo: batch $pagesToFetch to push to DB somehow.

// Config and setup
mb_internal_encoding("UTF-8");
$pagesToFetch = 1;
$dataFetcher = new DataFetcher();

// Debug
var_dump($argv);
$isDebugging = false;
if (count($argv) > 1) {
  $arg1 = $argv[1];
  if ($arg1 === "-debug")
    $isDebugging = true;
}

// Determine query options
if ($isDebugging) {
  $accountName = "realDonaldTrump";
  $tweetCount = 40;
} else {
  $accountName = Util::promptForValidUsername();
  $tweetCount = Util::promptForValidTweetCount();
}
$pagesToFetch = ceil($tweetCount / 20);

$accountTweets = $dataFetcher->fetchAndParse($pagesToFetch, $accountName);
DataPusher::push($accountTweets, $entityManager);

