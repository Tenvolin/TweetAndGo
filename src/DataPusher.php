<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-26
 * Time: 5:36 PM
 */
use Doctrine\ORM\EntityManager;

class DataPersist
{
  private $entityManager;
  private $conn;
  private $config;
  private $dataParser;
  private $dataFetcher;
  static $tweetBatch = 20;

  public function __construct($entityManager, $conn, $config)
  {
    $this->entityManager = $entityManager;
    $this->conn = $conn;
    $this->config = $config;

    $this->dataParser = new DataParser();
    $this->dataFetcher = new DataFetcher();

  }

  /**
   * @param $tweetArray
   * @param $entityManager EntityManager
   * @throws Exception
   */
  public static function pushTweetArray($tweetArray, $entityManager) {
    // todo: Should only insert; no need to generate entire tweet array.
    try {
      foreach ($tweetArray as $tweet) {
        $entityManager->persist($tweet);
      }

      $entityManager->flush();
      // todo: hold onto list of entities, check if each entity exists in the DB before insertion.
      //  Check against DB to see if there are any results that have the exact same tweetId.
    } catch (Exception $e) {
      // todo: error handle better here.
      throw $e;
    }
  }


  public function fetchAndPersistTweets($accountName, $tweetCount) {
    $dataParser = new DataParser();
    $dataFetcher = new DataFetcher();

    $entityManager = $this->entityManager;
    $conn = $this->conn;
    $config = $this->config;

    // ==== Persistence logic below.
    // 1) parse one page; then, attempt to insert. On exception thrown, insert all that we can.
    // 2) Pull the remainder from database entries;
    // 3) Should we not have enough database entries, pull the remaining entries.

    // insert until failure; on exception, generate new EntityManager.
    // pull last 20 for account.
    // filter out similar elts.
    // insert remainder. Pull the remaining DB entries.
//    $pagesToFetch = ceil($tweetCount / 20);
//    $pageFetchCount = 0;
//    $insertExceptionFound = false;


    for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
      $link = $dataParser->parseNextPageLink();
      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);
      $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?

      try {
        DataPersist::pushTweetArray($tweetArray, $entityManager);

      } catch (Exception $e) {
        // EntityManager object closes; must recreate and begin fetching from database instead.
        $insertExceptionFound = true;
        try {
          $entityManager = EntityManager::create($conn, $config);
        } catch (Exception $e) {
          throw new RuntimeException("EntityManager instantiation error: $e");
        }
        break;
      }
      // !!! return $insertExceptionFound, $remainingTweetsRequired.
    }

    // fetch from $fetchCount * 20, 20 tweets from the current account.
    if ($insertExceptionFound) {
      $offset = $pageFetchCount * self::$tweetBatch;
      $pageFetchCount++;

      $qb = $entityManager->createQueryBuilder();
      $qb->select('t') //t.id, t.tweetId, t.message, t.date, t.author
      ->from('Tweet', 't')
        ->where("t.author = $accountName")
        ->orderBy('t.tweetId', 'DESC')
        ->setFirstResult($offset)
        ->setMaxResults(self::$tweetBatch);
      $query = $qb->getQuery();

      $result = $query->getResult();
      $filteredResults = Util::filterExclusiveTweets($tweetArray, $result);

      try {
        DataPersist::pushTweetArray($filteredResults, $entityManager);
      } catch (Exception $e) {
        // terminate program,
        throw new RuntimeException("Unable to insert filtered exclusive tweets;
      something in persistence logic probably went wrong.");
      }
    }

    // pull possible remaining entries from db.
    // Ensure enough entries remain before pulling, so as to avoid db exceptions.
    $tweetsInDb = 0;
    $tweetsToFetchRemaining = 0;
    if ($pagesToFetch < $pageFetchCount) {
      $qb = null;
      $query = null;

      $qb = $entityManager->createQueryBuilder();
      $qb->select('count(t)')
        ->from('Tweet', 't')
        ->where("t.author = $accountName")
        ->orderBy('t.tweetId', 'DESC');
      $query = $qb->getQuery();

      $tweetsInDb = $query->getScalarResult();
      $tweetsToFetchRemaining = ($pageFetchCount - $pagesToFetch) * self::$tweetBatch;
    }



    if ($tweetsToFetchRemaining > 0) {
      $qb = null;
      $query = null;
      $result = null;
      $qb = $entityManager ->createQueryBuilder();

    // Fetch for the following scenarios:
    //  1) DB does not have any tweets required.
    //  2) DB has enough tweets required.
    //  3) Does not have enough tweets required, but some.
      if ($tweetsInDb == 0) {
        // Do nothing; nothing to pull from DB.
      } else if ($tweetsToFetchRemaining <= $tweetsInDb) {
        $qb->select('t')
          ->from('Tweet', 't')
          ->where("t.author = $accountName")
          ->orderBy('t.tweetId', 'DESC')
          ->setMaxResults($tweetsToFetchRemaining);

        $query = $qb->getQuery();
        $result = $query->getResult();
        $tweetsToFetchRemaining = 0; // DB fully satisfies tweet request.
      } else if ($tweetsToFetchRemaining > $tweetsInDb) {
        $qb->select('t')
          ->from('Tweet', 't')
          ->where("t.author = $accountName")
          ->orderBy('t.tweetId', 'DESC')
          ->setMaxResults($tweetsInDb);

        $query = $qb->getQuery();
        $result = $query->getResult();
        $tweetsToFetchRemaining -= $tweetsInDb;
      }
    }


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
  }

  // Attempt to fetch $tweetCount tweets and persist to DB.
  // On failure to persist,
  private function pushPossibleTweets($accountName, $tweetCount) {
    // todo: turn each large if/forloop block into helper functions like this.
    // todo: output only tweetsRemaining, and if an exception was found.
    //  That's the only logic we care about.
    $dataFetcher = $this->dataFetcher;
    $dataParser = $this->dataParser;
    $entityManager = $this->entityManager;
    $conn = $this->conn;
    $config = $this->config;

    $pagesToFetch = ceil($tweetCount / 20);
    $pageFetchCount = 0;
    $insertExceptionFound = false;

    for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
      $link = $dataParser->parseNextPageLink();
      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);
      $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?

      try {
        DataPersist::pushTweetArray($tweetArray, $entityManager);

      } catch (Exception $e) {
        // EntityManager object closes; must recreate and begin fetching from database instead.
        $insertExceptionFound = true;
        try {
          $this->entityManager = EntityManager::create($conn, $config);
        } catch (Exception $e) {
          throw new RuntimeException("EntityManager instantiation error: $e");
        }
        break;
      }
      // !!! return $insertExceptionFound, $remainingTweetsRequired.
    }

    return [$var1, $var2, $var3];
  }
}