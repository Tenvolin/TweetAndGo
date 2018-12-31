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
include_once "DataPersist.php";
include_once "Util.php";
include_once "model/Tweet.php";
include_once "ParseException.php";
include_once "ErrorParser.php";
include_once "Logger.php";

// todo: set up some unit testing.
// todo: attempt to detect any memory leaks.
// todo: ensure you close everything; dataParser and dataFetcher.

// Config and setup
mb_internal_encoding("UTF-8");
$pagesToFetch = 1;


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
  $accountName = "realDonaldTrump"; // miraieu, realDonaldTrump, ladygaga, selenagomez
  $tweetCount = 100;
} else {
  $accountName = Util::promptForValidUsername();
  $tweetCount = Util::promptForValidTweetCount();
}
$debug_logger = new Logger();

// Fetch enough data to satisfy.
//   public static function fetchAndPersistTweets($accountName, $tweetCount, $entityManager, $conn, $config) {
$dataPusher = new DataPersist($entityManager, $conn, $config);
$dataPusher->fetchAndPersistTweets($accountName, $tweetCount);


$debug_logger->close();