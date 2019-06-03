<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include_once "../../bootstrap.php";

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
if ($targetDebugOneAccount) {
  $accountName = "miraieu"; // miraieu, realDonaldTrump, ladygaga, selenagomez, taylorswift13
  $tweetCount = 40;

  $dataPusher = new DataPersist($entityManager, $conn, $config);
  $dataPusher->fetchAndPersistTweets($accountName, $tweetCount);

} else if ($targetOneAccount) {
  $accountName = CSVAccountParser::parseAccountName($argv);
  $tweetCount = CSVAccountParser::parseTweetCount($argv);

  $dataPusher = new DataPersist($entityManager, $conn, $config);
  $dataPusher->fetchAndPersistTweets($accountName, $tweetCount);

} else if ($targetCSVFileAccounts) {
  $filepath = CSVAccountParser::parseCSVFilepath($argv);
  $tweetCount = CSVAccountParser::parseTweetCount($argv);
  $accountNamesArray = CSVAccountParser::parseAccounts($filepath);

  $dataPusher = new DataPersist($entityManager, $conn, $config);
  foreach ($accountNamesArray as $accountName) {
    $dataPusher->fetchAndPersistTweets($accountName, $tweetCount);
  }

}

// ensure you close everything; dataParser and dataFetcher.
$debug_logger->close();


