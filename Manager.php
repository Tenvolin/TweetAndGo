<?php

use App\Core\Parse\CSVAccountParser;
use App\Core\Logger;
use App\Core\DataPersist;

include_once "config/bootstrap.php";

// Config and setup
mb_internal_encoding("UTF-8");
$pagesToFetch = 1;

// Are we debugging?
var_dump($argv);
$targetDebugOneAccount = false;
if (count($argv) > 1) {
  $arg1 = $argv[1];
  if ($arg1 === "-debug")
    $targetDebugOneAccount = true;
}
$debug_logger = new Logger();

// Determine what account we are querying.
$targetOneAccount = CSVAccountParser::checkIfTargetOneAccount($argv);
$targetCSVFileAccounts = CSVAccountParser::checkIfTargetCSV($argv);

// perform chosen high level action.
$dataPusher = new DataPersist($entityManager, $conn, $config);
if ($targetDebugOneAccount) {
  $accountName = "miraieu"; // miraieu, realDonaldTrump, ladygaga, selenagomez, taylorswift13
  $tweetCount = 40;
  $dataPusher->fetchAndPersistTweets($accountName, $tweetCount);

} else if ($targetOneAccount) {
  $accountName = CSVAccountParser::parseAccountName($argv);
  $tweetCount = CSVAccountParser::parseTweetCount($argv);
  $dataPusher->fetchAndPersistTweets($accountName, $tweetCount);

} else if ($targetCSVFileAccounts) {
  $filepath = CSVAccountParser::parseCSVFilepath($argv);
  $tweetCount = CSVAccountParser::parseTweetCount($argv);
  $accountNamesArray = CSVAccountParser::parseAccounts($filepath);
  foreach ($accountNamesArray as $accountName) {
    $dataPusher->fetchAndPersistTweets($accountName, $tweetCount);
  }
}

// ensure you close everything; dataParser and dataFetcher.
$debug_logger->close();