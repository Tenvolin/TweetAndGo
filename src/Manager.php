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

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
// todo: batch $pagesToFetch to push to DB somehow.

// Config and setup
mb_internal_encoding("UTF-8");
$pagesToFetch = 1;
$dataFetcher = new DataFetcher();
$dataParser = new DataParser();

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

// ==== Persistence logic below.
// 1) parse one page; then, attempt to insert. On exception thrown, insert all that we can.
// 2) Pull the remainder from database entries;
// 3) Should we not have enough database entries, pull the remaining entries.

// insert until failure; on exception, generate new EntityManager.
// pull last 20 for account.
// filter out similar elts.
// insert remainder. Pull the remaining DB entries.
$pageFetchCount = 0;
$insertExceptionFound = false;

for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
  $link = $dataParser->parseNextPageLink();
  $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
  $dataParser->loadHtmlStr($tweetsHtmlData);
  $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?

  try {
    DataPusher::push($tweetArray, $entityManager);

  } catch (Exception $e) {
    // EntityManager object closes; must recreate.
    $insertExceptionFound = true;
    try {
      $entityManager = EntityManager::create($conn, $config);
    } catch (Exception $e) {
      throw new RuntimeException("EntityManager instantiation error: $e");
    }

    break;
  }
}

// fetch from $fetchCount * 20, 20 tweets from the current account.
if ($insertExceptionFound) {
  $tweetBatch = 20;
  $offset = $pageFetchCount * $tweetBatch;

  $qb = $entityManager->createQueryBuilder();
  $qb->select('t') //t.id, t.tweetId, t.message, t.date, t.author
    ->from('Tweet', 't')
    ->orderBy('t.tweetId', 'DESC')
    ->setFirstResult($offset)
    ->setMaxResults($tweetBatch);

  $query = $qb->getQuery();
  $result = $query->getResult();

  $filteredResults = Util::filterOutsideEntities($tweetArray, $result);
  $i = 5;
}








$dataParser->close();