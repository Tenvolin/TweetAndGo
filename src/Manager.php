<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include_once "../bootstrap.php";
include_once "DataFetcher.php";
include_once "parse/DataParser.php";
include_once "DataPersist.php";
include_once "Util.php";
include_once "model/Tweet.php";
include_once "ParseException.php";
include_once "parse/ErrorParser.php";
include_once "Logger.php";

// Config and setup
mb_internal_encoding("UTF-8");
$pagesToFetch = 1;

// Are we debugging?
var_dump($argv);
$isDebugging = false;
if (count($argv) > 1) {
  $arg1 = $argv[1];
  if ($arg1 === "-debug")
    $isDebugging = true;
}

// Determine what account we are querying.
if ($isDebugging) {
  $accountName = "miraieu"; // miraieu, realDonaldTrump, ladygaga, selenagomez, taylorswift13
  $tweetCount = 3000;
} else {
  $accountName = Util::promptForValidUsername();
  $tweetCount = Util::promptForValidTweetCount();
}
$debug_logger = new Logger();

// Fetch enough tweets for current account.
$dataPusher = new DataPersist($entityManager, $conn, $config);
$dataPusher->fetchAndPersistTweets($accountName, $tweetCount);

// ensure you close everything; dataParser and dataFetcher.
$debug_logger->close();