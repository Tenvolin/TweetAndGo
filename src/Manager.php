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

// todo: batch $pagesToFetch to push to DB somehow.

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
  $accountName = "miraieu"; // miraieu
  $tweetCount = 600;
} else {
  $accountName = Util::promptForValidUsername();
  $tweetCount = Util::promptForValidTweetCount();
}

// Fetch enough data to satisfy.
//   public static function fetchAndPersistTweets($accountName, $tweetCount, $entityManager, $conn, $config) {
$dataPusher = new DataPersist($entityManager, $conn, $config);
$dataPusher->fetchAndPersistTweets($accountName, $tweetCount);

// Having persisted the data, the DB must have the available tweets;
// This will return the desired number of tweets; otherwise, return
// the max available tweets.
//$tweets = DataFetcher::fetchTweetsFromDb($accountName, $tweetCount); // todo: Implement.


// =============================== Refactor below.
//// ==== Persistence logic below.
//// 1) parse one page; then, attempt to insert. On exception thrown, insert all that we can.
//// 2) Pull the remainder from database entries;
//// 3) Should we not have enough database entries, pull the remaining entries.
//
//// insert until failure; on exception, generate new EntityManager.
//// pull last 20 for account.
//// filter out similar elts.
//// insert remainder. Pull the remaining DB entries.
//$pagesToFetch = ceil($tweetCount / 20);
//$pageFetchCount = 0;
//$insertExceptionFound = false;
//$tweetBatch = 20;
//
//for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
//  $link = $dataParser->parseNextPageLink();
//  $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
//  $dataParser->loadHtmlStr($tweetsHtmlData);
//  $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?
//
//  try {
//    DataPusher::pushTweetArray($tweetArray, $entityManager);
//
//  } catch (Exception $e) {
//    // EntityManager object closes; must recreate and begin fetching from database instead.
//    $insertExceptionFound = true;
//    try {
//      $entityManager = EntityManager::create($conn, $config);
//    } catch (Exception $e) {
//      throw new RuntimeException("EntityManager instantiation error: $e");
//    }
//    break;
//  }
//}
//
//// fetch from $fetchCount * 20, 20 tweets from the current account.
//if ($insertExceptionFound) {
//  $offset = $pageFetchCount * $tweetBatch;
//  $pageFetchCount++;
//
//  $qb = $entityManager->createQueryBuilder();
//  $qb->select('t') //t.id, t.tweetId, t.message, t.date, t.author
//    ->from('Tweet', 't')
//    ->where("t.author = $accountName")
//    ->orderBy('t.tweetId', 'DESC')
//    ->setFirstResult($offset)
//    ->setMaxResults($tweetBatch);
//  $query = $qb->getQuery();
//
//  $result = $query->getResult();
//  $filteredResults = Util::filterExclusiveTweets($tweetArray, $result);
//
//  try {
//    DataPusher::pushTweetArray($filteredResults, $entityManager);
//  } catch (Exception $e) {
//    // terminate program,
//    throw new RuntimeException("Unable to insert filtered exclusive tweets;
//      something in persistence logic probably went wrong.");
//  }
//}
//
//// pull possible remaining entries from db.
//// Ensure enough entries remain before pulling, so as to avoid db exceptions.
//$tweetsInDb = 0;
//$tweetsToFetchRemaining = 0;
//if ($pagesToFetch < $pageFetchCount) {
//  $qb = null;
//  $query = null;
//
//  $qb = $entityManager->createQueryBuilder();
//  $qb->select('count(t)')
//    ->from('Tweet', 't')
//    ->where("t.author = $accountName")
//    ->orderBy('t.tweetId', 'DESC');
//  $query = $qb->getQuery();
//
//  $tweetsInDb = $query->getScalarResult();
//  $tweetsToFetchRemaining = ($pageFetchCount - $pagesToFetch) * $tweetBatch;
//}
//
//
//
//if ($tweetsToFetchRemaining > 0) {
//  $qb = null;
//  $query = null;
//  $result = null;
//  $qb = $entityManager ->createQueryBuilder();
//
//  // Fetch for the following scenarios:
////  1) DB does not have any tweets required.
////  2) DB has enough tweets required.
////  3) Does not have enough tweets required, but some.
//  if ($tweetsInDb == 0) {
//    // Do nothing; nothing to pull from DB.
//  } else if ($tweetsToFetchRemaining <= $tweetsInDb) {
//    $qb->select('t')
//      ->from('Tweet', 't')
//      ->where("t.author = $accountName")
//      ->orderBy('t.tweetId', 'DESC')
//      ->setMaxResults($tweetsToFetchRemaining);
//
//    $query = $qb->getQuery();
//    $result = $query->getResult();
//    $tweetsToFetchRemaining = 0; // DB fully satisfies tweet request.
//  } else if ($tweetsToFetchRemaining > $tweetsInDb) {
//    $qb->select('t')
//      ->from('Tweet', 't')
//      ->where("t.author = $accountName")
//      ->orderBy('t.tweetId', 'DESC')
//      ->setMaxResults($tweetsInDb);
//
//    $query = $qb->getQuery();
//    $result = $query->getResult();
//    $tweetsToFetchRemaining -= $tweetsInDb;
//  }
//}


//// fetch the remaining possible entries from twitter, when DB is exhausted.
//// pull the last tweet.
//// This will not trigger if DB was never prompted.
//if ($tweetsToFetchRemaining >= 0) {
//  $qb = null;
//  $query = null;
//  $result = null;
//  $qb = $entityManager ->createQueryBuilder();
//
//  // Generate nextPageLink via last tweet stored in DB.
//  $qb->select('t')
//    ->from('Tweet', 't')
//    ->where("t.author = $accountName")
//    ->orderBy('t.tweetId', 'ASC')
//    ->setMaxResults(1);
//
//
//  // fetch remaining tweets
//  for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
//    $link = $dataParser->parseNextPageLink();
//    $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
//    $dataParser->loadHtmlStr($tweetsHtmlData);
//    $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?
//
//    try {
//      DataPusher::pushTweetArray($tweetArray, $entityManager);
//
//    } catch (Exception $e) {
//      // EntityManager object closes; must recreate and begin fetching from database instead.
//      $insertExceptionFound = true;
//      try {
//        $entityManager = EntityManager::create($conn, $config);
//      } catch (Exception $e) {
//        throw new RuntimeException("EntityManager instantiation error: $e");
//      }
//      break;
//    }
//  }
//}